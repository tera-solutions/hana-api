<?php

namespace App\Modules\Education\Score\Services;

use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\Education\Grade\Models\Grade;
use App\Modules\Education\Score\Models\ScoreConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * EDU-17 "Bảng điểm tổng hợp & chốt điểm": a class defines weighted score
 * components (`ScoreConfig`), the teacher enters each student's raw score per
 * component (`edu_grades` rows keyed by `type` = component key), and
 * `finalize()` computes+locks one weighted-average row per student
 * (`type` = "final").
 */
class ScoreService
{
    public function getConfig($classId): ?ScoreConfig
    {
        return ScoreConfig::where('class_id', $classId)->first();
    }

    /**
     * @param  array<int, array{key: string, label: string, weight: float}>  $components
     *
     * @throws \RuntimeException
     */
    public function saveConfig($classId, array $components): ScoreConfig
    {
        $this->assertNotFinalized($classId, 'Lớp đã chốt điểm, hãy mở khóa trước khi đổi cấu trúc điểm.');

        $keys = array_map(fn ($c) => $c['key'], $components);
        if (count($keys) !== count(array_unique($keys))) {
            throw new \RuntimeException('Mã thành phần điểm bị trùng.');
        }
        if (in_array(Grade::TYPE_FINAL, $keys, true)) {
            throw new \RuntimeException(
                'Mã thành phần điểm không được đặt là "'.Grade::TYPE_FINAL.'" — đây là mã dành riêng cho điểm tổng kết đã chốt.',
            );
        }

        $totalWeight = array_sum(array_map(fn ($c) => (float) $c['weight'], $components));
        if (abs($totalWeight - 100.0) > 0.01) {
            throw new \RuntimeException('Tổng trọng số các thành phần điểm phải bằng 100%.');
        }

        $config = ScoreConfig::updateOrCreate(
            ['class_id' => $classId],
            ['components' => $components],
        );

        return $config;
    }

    /**
     * Roster + entered component scores + final (if any), for the score board UI.
     */
    public function board($classId): array
    {
        $config = $this->getConfig($classId);

        $roster = Enrollment::where('class_id', $classId)
            ->whereIn('status', Enrollment::ACTIVE_STATUSES)
            ->with('student')
            ->get();

        $grades = Grade::where('class_id', $classId)->get()->groupBy('student_id');

        $students = $roster->map(function (Enrollment $enrollment) use ($grades) {
            $studentGrades = $grades->get($enrollment->student_id, collect());
            $final = $studentGrades->firstWhere('type', Grade::TYPE_FINAL);

            return [
                'student_id' => $enrollment->student_id,
                'student' => $enrollment->student ? [
                    'id' => $enrollment->student->id,
                    'student_code' => $enrollment->student->student_code,
                    'full_name' => $enrollment->student->full_name,
                ] : null,
                'components' => $studentGrades
                    ->where('type', '!=', Grade::TYPE_FINAL)
                    ->pluck('score', 'type'),
                'final_score' => $final?->score,
                'finalized_at' => $final?->finalized_at,
            ];
        })->values();

        return [
            'config' => $config,
            'is_finalized' => $students->contains(fn ($s) => $s['final_score'] !== null),
            'students' => $students,
        ];
    }

    public function saveComponentScore($classId, $studentId, string $type, float $score): Grade
    {
        $this->assertNotFinalized($classId, 'Lớp đã chốt điểm, hãy mở khóa trước khi sửa điểm thành phần.');

        $config = $this->getConfig($classId);
        if (! $config || ! collect($config->components)->contains('key', $type)) {
            throw new \RuntimeException('Thành phần điểm không hợp lệ — hãy cấu hình trước.');
        }

        return Grade::updateOrCreate(
            ['class_id' => $classId, 'student_id' => $studentId, 'type' => $type],
            ['score' => $score],
        );
    }

    /**
     * Compute the weighted final score for every actively-enrolled student and
     * lock it. Every student must already have a score for every configured
     * component — the offending students are named in the exception so the
     * teacher knows exactly who's missing data.
     *
     * @throws \RuntimeException
     */
    public function finalize($classId): array
    {
        $config = $this->getConfig($classId);
        if (! $config) {
            throw new \RuntimeException('Chưa cấu hình trọng số điểm cho lớp này.');
        }

        $components = collect($config->components);
        $board = $this->board($classId);

        $missing = [];
        foreach ($board['students'] as $row) {
            $missingKeys = $components->pluck('key')->diff($row['components']->keys());
            if ($missingKeys->isNotEmpty()) {
                $missing[] = $row['student']['full_name'] ?? "#{$row['student_id']}";
            }
        }

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Còn học viên chưa nhập đủ điểm thành phần: '.implode(', ', $missing),
            );
        }

        return DB::transaction(function () use ($classId, $components, $board) {
            $userId = Auth::guard('api')->id();
            $finalized = [];

            foreach ($board['students'] as $row) {
                $weighted = $components->sum(
                    fn ($c) => (float) $row['components'][$c['key']] * ((float) $c['weight'] / 100),
                );

                $finalized[] = Grade::updateOrCreate(
                    ['class_id' => $classId, 'student_id' => $row['student_id'], 'type' => Grade::TYPE_FINAL],
                    [
                        'score' => round($weighted, 2),
                        'breakdown' => $row['components'],
                        'finalized_at' => now(),
                        'finalized_by' => $userId,
                    ],
                );
            }

            return $finalized;
        });
    }

    public function unlock($classId): void
    {
        Grade::where('class_id', $classId)->where('type', Grade::TYPE_FINAL)->delete();
    }

    private function assertNotFinalized($classId, string $message): void
    {
        $locked = Grade::where('class_id', $classId)->where('type', Grade::TYPE_FINAL)->exists();
        if ($locked) {
            throw new \RuntimeException($message);
        }
    }
}
