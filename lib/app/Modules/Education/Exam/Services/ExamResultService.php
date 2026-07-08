<?php

namespace App\Modules\Education\Exam\Services;

use App\Modules\Education\Exam\Models\Exam;
use App\Modules\Education\Exam\Models\ExamRegistration;
use App\Modules\Education\Exam\Models\ExamResult;
use App\Modules\Education\StudentLevel\Models\StudentLevel;
use App\Modules\Education\StudentLevel\Services\StudentLevelService;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Support\Facades\DB;

class ExamResultService
{
    /**
     * The six gradable skills and the columns they map to on edu_exam_results.
     */
    private const SKILL_COLUMNS = [
        'listening_score',
        'speaking_score',
        'reading_score',
        'writing_score',
        'grammar_score',
        'vocabulary_score',
    ];

    /**
     * Grade a participant's sitting (exam.md §XI). Skill scores are summed into the
     * total; BR006 caps that total at the exam's maximum. Upserts the result row keyed
     * by (session, student) and advances the registration to GRADED.
     *
     * @throws \RuntimeException
     */
    public function grade($registrationId, array $data): ExamResult
    {
        return DB::transaction(function () use ($registrationId, $data) {
            $registration = ExamRegistration::with('session.exam')->findOrFail($registrationId);

            $this->authorizeRegistration($registration);

            $exam = $registration->session->exam;

            $scores = [];
            $total = 0.0;
            foreach (self::SKILL_COLUMNS as $column) {
                $value = $data[$column] ?? null;
                $scores[$column] = $value;
                $total += (float) ($value ?? 0);
            }

            // BR006: combined skill scores cannot exceed the exam's total.
            if ($total > (float) $exam->total_score) {
                throw new \RuntimeException('Tổng điểm các kỹ năng vượt quá điểm tối đa của bài thi.');
            }

            $passed = $total >= (float) $exam->passing_score;

            $result = ExamResult::updateOrCreate(
                [
                    'exam_session_id' => $registration->exam_session_id,
                    'student_id' => $registration->student_id,
                ],
                [
                    ...$scores,
                    'total_score' => $total,
                    'grade' => $this->classify($total, $exam),
                    'passed' => $passed,
                    'published_at' => null,
                ],
            );

            $registration->update(['status' => ExamRegistration::STATUS_GRADED]);

            return $result->fresh('student');
        });
    }

    /**
     * Publish a graded result to the student (exam.md §XII). Stamps published_at and
     * advances the registration to PUBLISHED.
     *
     * @throws \RuntimeException
     */
    public function publish($registrationId): ExamResult
    {
        return DB::transaction(function () use ($registrationId) {
            $registration = ExamRegistration::with('session')->findOrFail($registrationId);

            $this->authorizeRegistration($registration);

            $result = ExamResult::where('exam_session_id', $registration->exam_session_id)
                ->where('student_id', $registration->student_id)
                ->first();

            if (! $result) {
                throw new \RuntimeException('Chưa có kết quả để công bố. Vui lòng chấm thi trước.');
            }

            $result->update(['published_at' => now()]);
            $registration->update(['status' => ExamRegistration::STATUS_PUBLISHED]);

            return $result->fresh('student');
        });
    }

    /**
     * Promote the participant to the next level based on a passing result (exam.md §XIII).
     * BR008 (minimum score) is enforced here; the resulting transition is recorded in
     * edu_student_level_histories with a back-reference to this exam result.
     *
     * NOTE: BR009 (course completion) and BR010 (no outstanding debt) depend on data not
     * yet aggregated and are intentionally not enforced, mirroring StudentLevelService.
     *
     * @throws \RuntimeException
     */
    public function promote($registrationId, array $data): StudentLevel
    {
        return DB::transaction(function () use ($registrationId, $data) {
            $registration = ExamRegistration::with('session')->findOrFail($registrationId);

            $this->authorizeRegistration($registration);

            $result = ExamResult::where('exam_session_id', $registration->exam_session_id)
                ->where('student_id', $registration->student_id)
                ->first();

            // BR008: only a passing result qualifies for promotion.
            if (! $result || ! $result->passed) {
                throw new \RuntimeException('Học viên chưa đạt điểm tối thiểu để xét lên cấp.');
            }

            $studentLevel = StudentLevel::where('student_id', $registration->student_id)->first();

            if (! $studentLevel) {
                throw new \RuntimeException('Học viên chưa được gán cấp độ.');
            }

            return app(StudentLevelService::class)->promote($studentLevel->id, [
                'target_level_id' => $data['target_level_id'] ?? null,
                'reason' => $data['reason'] ?? 'Xét lên cấp theo kết quả thi.',
                'exam_result_id' => $result->id,
            ]);
        });
    }

    /**
     * Guard grading/publishing/promotion: throws 403 when the registration's
     * sitting is not the teacher's (owns its class, or is its invigilator).
     */
    private function authorizeRegistration(ExamRegistration $registration): void
    {
        if ($scope = TeacherScope::current()) {
            $session = $registration->session;
            $owned = in_array((int) $session->class_room_id, $scope->classIds(), true)
                || (int) $session->teacher_id === $scope->teacherId;

            if (! $owned) {
                throw new \Package\Exception\AuthorizationException('Bạn không có quyền truy cập kỳ thi này.');
            }
        }
    }

    /**
     * Classify a total against the exam scale (exam.md §XII): ≥90% excellent,
     * ≥80% good, ≥70% pass, otherwise fail.
     */
    private function classify(float $total, Exam $exam): string
    {
        $max = (float) $exam->total_score;
        $percentage = $max > 0 ? ($total / $max) * 100 : 0;

        return match (true) {
            $percentage >= 90 => ExamResult::GRADE_EXCELLENT,
            $percentage >= 80 => ExamResult::GRADE_GOOD,
            $percentage >= 70 => ExamResult::GRADE_PASS,
            default => ExamResult::GRADE_FAIL,
        };
    }
}
