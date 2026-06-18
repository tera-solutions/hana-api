<?php

namespace App\Modules\Education\Assignment\Services;

use App\Helpers\Task;
use App\Modules\Education\Assignment\Models\Assignment;
use App\Modules\Education\Assignment\Models\AssignmentSubmission;
use App\Modules\Education\Assignment\Models\AssignmentSubmissionFile;
use App\Modules\Education\Assignment\Models\AssignmentTarget;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\Student\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class AssignmentService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable list (assignment.md §XIII).
     */
    public function paginate(array $params = [])
    {
        $query = Assignment::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('assignment_code', 'like', "%{$search}%")
                    ->orWhere('assignment_name', 'like', "%{$search}%");
            });
        }

        foreach (['assignment_type', 'course_id', 'class_room_id', 'lesson_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applySort($query, $params, ['assignment_code', 'assignment_name', 'assignment_type', 'due_date', 'status', 'created_at']);

        return $query->with(['course', 'classRoom'])->withCount('submissions')->paginate($this->resolvePerPage($params));
    }

    public function find($id): Assignment
    {
        return Assignment::with(['course', 'lesson', 'classRoom'])->findOrFail($id);
    }

    /**
     * Detail with submissions and progress counters (assignment.md §XI, §XII).
     */
    public function detail($id): array
    {
        $assignment = Assignment::with(['course', 'lesson', 'classRoom', 'submissions.student', 'submissions.files'])
            ->findOrFail($id);

        $byStatus = $assignment->submissions->groupBy('status')->map->count();

        return [
            'assignment' => $assignment,
            'progress' => [
                'total' => $assignment->submissions->count(),
                'submitted' => ($byStatus[AssignmentSubmission::STATUS_SUBMITTED] ?? 0) + ($byStatus[AssignmentSubmission::STATUS_LATE_SUBMITTED] ?? 0),
                'graded' => ($byStatus[AssignmentSubmission::STATUS_GRADED] ?? 0) + ($byStatus[AssignmentSubmission::STATUS_REVIEWED] ?? 0),
                'pending' => $byStatus[AssignmentSubmission::STATUS_ASSIGNED] ?? 0,
            ],
        ];
    }

    public function create(array $data): Assignment
    {
        return DB::transaction(function () use ($data) {
            $assignment = new Assignment($data);
            $assignment->assignment_code = $this->generateCode();
            $assignment->status = Assignment::STATUS_DRAFT;
            $assignment->save();

            return $this->find($assignment->id);
        });
    }

    /**
     * Generate the next human-readable assignment code (e.g. ASG000001).
     */
    private function generateCode(): string
    {
        $count = Task::setAndGetReferenceCount('assignment');

        return Task::generateReferenceNumber('assignment', $count, 'ASG');
    }

    public function update($id, array $data): Assignment
    {
        $assignment = Assignment::findOrFail($id);

        unset($data['id'], $data['assignment_code'], $data['status']);

        $assignment->update($data);

        return $this->find($assignment->id);
    }

    /**
     * Publish a draft so it can be assigned (assignment.md §XVII).
     *
     * @throws \RuntimeException
     */
    public function publish($id): Assignment
    {
        $assignment = Assignment::findOrFail($id);

        if ($assignment->status === Assignment::STATUS_PUBLISHED) {
            throw new \RuntimeException('Bài tập đã được giao.');
        }

        $assignment->update(['status' => Assignment::STATUS_PUBLISHED]);

        return $this->find($assignment->id);
    }

    public function delete($id): void
    {
        Assignment::findOrFail($id)->delete();
    }

    // ── Assigning (assignment.md §VII) ───────────────────────────────────────────

    /**
     * Assign to every active student of a class (assignment.md §VII "Theo lớp học").
     *
     * @return array{assigned: int}
     *
     * @throws \RuntimeException
     */
    public function assignByClass($id, int $classRoomId): array
    {
        return $this->seedTargets($id, $this->activeClassStudents($classRoomId));
    }

    /**
     * Assign to every active student of a level cohort (assignment.md §VII "Theo nhóm học viên").
     *
     * @return array{assigned: int}
     *
     * @throws \RuntimeException
     */
    public function assignByGroup($id, int $levelId): array
    {
        $studentIds = Student::query()
            ->where('level_id', $levelId)
            ->where('status', Student::STATUS_ACTIVE)
            ->pluck('id');

        return $this->seedTargets($id, $studentIds);
    }

    /**
     * Assign to an explicit list of students (assignment.md §VII "Theo học viên").
     *
     * @param  array<int>  $studentIds
     * @return array{assigned: int}
     *
     * @throws \RuntimeException
     */
    public function assignByStudent($id, array $studentIds): array
    {
        return $this->seedTargets($id, collect($studentIds));
    }

    /**
     * Assign to every active student of the lesson's class (assignment.md §VII "Giao tự động từ lesson").
     *
     * @return array{assigned: int}
     *
     * @throws \RuntimeException
     */
    public function assignByLesson($id, int $lessonId): array
    {
        $classId = (int) Lesson::findOrFail($lessonId)->class_room_id;

        return $this->seedTargets($id, $this->activeClassStudents($classId));
    }

    /**
     * Active student ids enrolled in a class.
     */
    private function activeClassStudents(int $classId): Collection
    {
        return DB::table('edu_class_students')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->pluck('student_id');
    }

    /**
     * Seed an assignment target + ASSIGNED submission per student (BR004).
     * Idempotent per (assignment, student). Only published assignments may be assigned.
     *
     * @return array{assigned: int}
     *
     * @throws \RuntimeException
     */
    private function seedTargets($id, Collection $studentIds): array
    {
        return DB::transaction(function () use ($id, $studentIds) {
            $assignment = Assignment::findOrFail($id);

            if ($assignment->status !== Assignment::STATUS_PUBLISHED) {
                throw new \RuntimeException('Chỉ bài tập đã giao (published) mới có thể gán cho học viên.');
            }

            $studentIds = $studentIds->map(fn ($v) => (int) $v)->unique()->values();

            if ($studentIds->isEmpty()) {
                throw new \RuntimeException('Không có học viên nào để giao bài.');
            }

            // A student is "assigned" iff they already hold a submission (BR004); seed
            // only the missing ones so the operation stays idempotent across re-assigns.
            $existing = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id');

            $newStudentIds = $studentIds->diff($existing)->values();

            if ($newStudentIds->isEmpty()) {
                return ['assigned' => 0];
            }

            $now = now();
            $actorId = Auth::guard('api')->id() ?? Auth::id();

            AssignmentTarget::insertOrIgnore($newStudentIds->map(fn (int $studentId) => [
                'assignment_id' => $assignment->id,
                'student_id' => $studentId,
                'assigned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            // BR004: each assigned student gets an ASSIGNED submission.
            AssignmentSubmission::insertOrIgnore($newStudentIds->map(fn (int $studentId) => [
                'assignment_id' => $assignment->id,
                'student_id' => $studentId,
                'status' => AssignmentSubmission::STATUS_ASSIGNED,
                'result_published' => false,
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            return ['assigned' => $newStudentIds->count()];
        });
    }

    // ── Submitting (assignment.md §VIII) ─────────────────────────────────────────

    /**
     * Record a student's submission (BR005/BR006/BR007).
     *
     * @throws \RuntimeException
     */
    public function submit($id, array $data): AssignmentSubmission
    {
        return DB::transaction(function () use ($id, $data) {
            $assignment = Assignment::findOrFail($id);
            $studentId = (int) $data['student_id'];

            $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('student_id', $studentId)
                ->first();

            // BR005: only an assigned student may submit.
            if (! $submission) {
                throw new \RuntimeException('Chỉ học viên được giao mới được nộp bài.');
            }

            $alreadySubmitted = in_array($submission->status, [
                AssignmentSubmission::STATUS_SUBMITTED,
                AssignmentSubmission::STATUS_LATE_SUBMITTED,
                AssignmentSubmission::STATUS_GRADED,
                AssignmentSubmission::STATUS_REVIEWED,
            ], true);

            // BR007: re-submission only when allowed.
            if ($alreadySubmitted && ! $assignment->allow_multiple_submission) {
                throw new \RuntimeException('Bài tập không cho phép nộp lại.');
            }

            // BR006: late submission only when allowed.
            $late = now()->greaterThan($assignment->due_date);
            if ($late && ! $assignment->allow_late_submission) {
                throw new \RuntimeException('Đã quá hạn nộp bài.');
            }

            $submission->update([
                'submitted_at' => now(),
                'answer' => $data['answer'] ?? null,
                'status' => $late ? AssignmentSubmission::STATUS_LATE_SUBMITTED : AssignmentSubmission::STATUS_SUBMITTED,
                // A re-submission supersedes the previous grade.
                'score' => null,
                'comment' => null,
                'result_published' => false,
            ]);

            // Replace attached files with the new set.
            $submission->files()->delete();
            foreach ($data['files'] ?? [] as $file) {
                AssignmentSubmissionFile::create([
                    'submission_id' => $submission->id,
                    'file_id' => $file['file_id'],
                    'file_name' => $file['file_name'] ?? null,
                ]);
            }

            return $submission->fresh('files');
        });
    }

    // ── Grading & results (assignment.md §IX, §X) ────────────────────────────────

    /**
     * Grade a submission (BR008).
     *
     * @throws \RuntimeException
     */
    public function grade($submissionId, array $data): AssignmentSubmission
    {
        $submission = AssignmentSubmission::with('assignment')->findOrFail($submissionId);

        // BR008: score cannot exceed the assignment's max score.
        if ((float) $data['score'] > (float) $submission->assignment->max_score) {
            throw new \RuntimeException('Điểm không được vượt quá điểm tối đa.');
        }

        $submission->update([
            'score' => $data['score'],
            'comment' => $data['comment'] ?? $submission->comment,
            'status' => AssignmentSubmission::STATUS_GRADED,
        ]);

        return $submission->fresh(['files', 'student']);
    }

    /**
     * Publish a graded result to the student (assignment.md §X).
     *
     * @throws \RuntimeException
     */
    public function publishResult($submissionId): AssignmentSubmission
    {
        $submission = AssignmentSubmission::findOrFail($submissionId);

        if (! in_array($submission->status, [AssignmentSubmission::STATUS_GRADED, AssignmentSubmission::STATUS_REVIEWED], true)) {
            throw new \RuntimeException('Chỉ có thể công bố bài đã chấm điểm.');
        }

        $submission->update([
            'result_published' => true,
            'status' => AssignmentSubmission::STATUS_REVIEWED,
        ]);

        return $submission->fresh(['files', 'student']);
    }
}
