<?php

namespace App\Modules\Education\StudentLevel\Services;

use App\Modules\Education\Level\Models\Level;
use App\Modules\Education\Student\Models\Student;
use App\Modules\Education\StudentLevel\Models\StudentLevel;
use App\Modules\Education\StudentLevel\Models\StudentLevelAssessment;
use App\Modules\Education\StudentLevel\Models\StudentLevelHistory;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class StudentLevelService
{
    use HandlesEntityQueries;

    private const PROGRESS_WEIGHTS = [
        'attendance' => 0.20,
        'assignment' => 0.20,
        'evaluation' => 0.30,
        'exam' => 0.30,
    ];

    /**
     * Current level of a student with progress and history (student-level.md §XI).
     *
     * @throws \RuntimeException
     */
    public function detailByStudent($studentId): array
    {
        $query = StudentLevel::with(['student', 'course', 'level'])
            ->where('student_id', $studentId);

        if ($scope = TeacherScope::current()) {
            $scope->constrainByStudentId($query, 'edu_student_levels.student_id');
        }

        $studentLevel = $query->first();

        if (! $studentLevel) {
            throw new \RuntimeException('Học viên chưa được gán cấp độ.');
        }

        return [
            'student_level' => $studentLevel,
            'progress' => $this->progress($studentLevel),
            'histories' => StudentLevelHistory::with(['fromLevel', 'toLevel'])
                ->where('student_level_id', $studentLevel->id)
                ->latest()->get(),
        ];
    }

    /**
     * Placement: record an assessment and assign the resulting level
     * (student-level.md §VI, §VII). Idempotent on the student's single current
     * level row (BR001).
     *
     * @throws \RuntimeException
     */
    public function placement(array $data): StudentLevel
    {
        return DB::transaction(function () use ($data) {
            $student = Student::findOrFail($data['student_id']);

            if ($scope = TeacherScope::current()) {
                $scope->authorizeStudent((int) $student->id);
            }

            $level = $this->levelInCourse((int) $data['level_id'], (int) $data['course_id']); // BR002

            StudentLevelAssessment::create([
                'student_id' => $student->id,
                'assessment_type' => $data['assessment_type'] ?? StudentLevelAssessment::TYPE_PLACEMENT_TEST,
                'score' => $data['score'] ?? null,
                'level_id' => $level->id,
                'comment' => $data['comment'] ?? null,
                'assessed_by' => $this->actorId(),
                'assessed_at' => now(),
            ]);

            $current = StudentLevel::where('student_id', $student->id)->first();
            $fromLevelId = $current?->level_id;

            $studentLevel = StudentLevel::updateOrCreate(
                ['student_id' => $student->id], // BR001: one current level per student.
                [
                    'business_id' => $student->business_id,
                    'course_id' => $level->course_id,
                    'level_id' => $level->id,
                    'assigned_at' => now(),
                    'assigned_by' => $this->actorId(),
                    'placement_score' => $data['score'] ?? null,
                    'status' => StudentLevel::STATUS_ACTIVE,
                ],
            );

            $this->logHistory($studentLevel, 'placement', $fromLevelId, $level->id, $data['comment'] ?? null, $data['score'] ?? null);

            return $studentLevel->fresh(['student', 'course', 'level']);
        });
    }

    /**
     * Promote to the next level in the learning path, or to an explicit target
     * (student-level.md §IX). The target must belong to the same course and sit
     * higher in the path.
     *
     * NOTE: the academic gates BR003–BR006 (course completion, attendance ≥ 80%,
     * average score, no tuition debt) depend on attendance/exam/debt data that is
     * not yet wired in; they are intentionally not enforced here.
     *
     * @throws \RuntimeException
     */
    public function promote($studentLevelId, array $data): StudentLevel
    {
        return DB::transaction(function () use ($studentLevelId, $data) {
            $studentLevel = $this->scopedStudentLevel($studentLevelId);
            $current = $studentLevel->level_id ? Level::find($studentLevel->level_id) : null;

            $target = ! empty($data['target_level_id'])
                ? $this->levelInCourse((int) $data['target_level_id'], (int) $studentLevel->course_id)
                : $this->nextLevel($studentLevel, $current);

            if ($current && $target->level_order <= $current->level_order) {
                throw new \RuntimeException('Cấp độ mục tiêu phải cao hơn cấp độ hiện tại.');
            }

            $studentLevel->update(['level_id' => $target->id, 'assigned_at' => now(), 'assigned_by' => $this->actorId()]);

            $this->logHistory($studentLevel, 'promote', $current?->id, $target->id, $data['reason'] ?? null, null, $data['exam_result_id'] ?? null);

            return $studentLevel->fresh(['student', 'course', 'level']);
        });
    }

    /**
     * Manually adjust a student's level (student-level.md §X) — e.g. a mis-placement
     * or programme transfer. The target must belong to the same course (BR002).
     *
     * @throws \RuntimeException
     */
    public function adjust($studentLevelId, array $data): StudentLevel
    {
        return DB::transaction(function () use ($studentLevelId, $data) {
            $studentLevel = $this->scopedStudentLevel($studentLevelId);
            $target = $this->levelInCourse((int) $data['target_level_id'], (int) $studentLevel->course_id);

            $fromLevelId = $studentLevel->level_id;

            $studentLevel->update(['level_id' => $target->id, 'assigned_at' => now(), 'assigned_by' => $this->actorId()]);

            $this->logHistory($studentLevel, 'adjust', $fromLevelId, $target->id, $data['reason']);

            return $studentLevel->fresh(['student', 'course', 'level']);
        });
    }

    /**
     * Full change history of a student's level (student-level.md §XI "Tab Lịch sử").
     */
    public function history($studentLevelId)
    {
        $this->scopedStudentLevel($studentLevelId);

        return StudentLevelHistory::with(['fromLevel', 'toLevel'])
            ->where('student_level_id', $studentLevelId)
            ->latest()->get();
    }

    /**
     * Resolve a student-level row within the caller's teacher scope, 404-ing
     * when the student is not enrolled in any of the teacher's classes.
     */
    private function scopedStudentLevel($id): StudentLevel
    {
        $query = StudentLevel::query();

        if ($scope = TeacherScope::current()) {
            $scope->constrainByStudentId($query, 'edu_student_levels.student_id');
        }

        return $query->findOrFail($id);
    }

    /**
     * Resolve a level and assert it belongs to the given course (BR002).
     *
     * @throws \RuntimeException
     */
    private function levelInCourse(int $levelId, int $courseId): Level
    {
        $level = Level::findOrFail($levelId);

        if ((int) $level->course_id !== $courseId) {
            throw new \RuntimeException('Cấp độ không thuộc khóa học đã chọn.');
        }

        return $level;
    }

    /**
     * The next level in the same course's path.
     *
     * @throws \RuntimeException
     */
    private function nextLevel(StudentLevel $studentLevel, ?Level $current): Level
    {
        $query = Level::where('course_id', $studentLevel->course_id)
            ->where('status', Level::STATUS_ACTIVE)
            ->orderBy('level_order');

        if ($current) {
            $query->where('level_order', '>', $current->level_order);
        }

        $next = $query->first();

        if (! $next) {
            throw new \RuntimeException('Không còn cấp độ cao hơn trong lộ trình.');
        }

        return $next;
    }

    /**
     * Progress indicators (student-level.md §VIII). The data sources (attendance,
     * assignment, evaluation, exam) are not yet aggregated, so these are structural
     * placeholders alongside the spec's weighting.
     */
    private function progress(StudentLevel $studentLevel): array
    {
        return [
            'attendance' => 0,
            'assignment' => 0,
            'evaluation' => 0,
            'exam' => 0,
            'progress_score' => 0,
            'weights' => self::PROGRESS_WEIGHTS,
        ];
    }

    private function logHistory(StudentLevel $studentLevel, string $action, ?int $fromLevelId, int $toLevelId, ?string $reason, $score = null, ?int $examResultId = null): void
    {
        StudentLevelHistory::create([
            'student_level_id' => $studentLevel->id,
            'business_id' => $studentLevel->business_id,
            'student_id' => $studentLevel->student_id,
            'from_level_id' => $fromLevelId,
            'to_level_id' => $toLevelId,
            'source' => $action,
            'action' => $action,
            'reason' => $reason,
            'score' => $score,
            'exam_result_id' => $examResultId,
            'created_by' => $this->actorId(),
            'effective_at' => now(),
        ]);
    }

    private function actorId(): ?int
    {
        return Auth::guard('api')->id() ?? Auth::id();
    }
}
