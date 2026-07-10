<?php

namespace App\Modules\Education\Student\Services;

use App\Helpers\Task;
use App\Modules\CRM\Parent\Models\ParentModel;
use App\Modules\Education\Student\Enums\StudentStatus;
use App\Modules\Education\Student\Events\StudentCreated;
use App\Modules\Education\Student\Models\Student;
use App\Modules\Education\Student\Models\StudentHistory;
use App\Modules\Education\Support\SummarizesByStatus;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Package\Database\Concerns\HandlesEntityQueries;

class StudentService
{
    use HandlesEntityQueries;
    use SummarizesByStatus;

    /** Fields stored in edu_student_profiles rather than on edu_students. */
    private const PROFILE_FIELDS = ['address', 'province', 'district', 'school', 'grade', 'note'];

    /**
     * Paginated, searchable, filterable, sortable list.
     */
    public function paginate(array $params = [])
    {
        $query = $this->baseQuery($params);

        $this->applySort($query, $params, ['code', 'name', 'enrollment_date', 'created_at']);

        return $query->with(['business', 'branch', 'level', 'parents'])
            ->paginate($this->resolvePerPage($params));
    }

    /**
     * Aggregate counters for the list view, honouring the same filters/scope as
     * {@see paginate()}.
     *
     * @return array{total: int, by_status: array<string, int>, new_this_month: int}
     */
    public function summary(array $params = []): array
    {
        $byStatus = (clone $this->baseQuery($params))
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status');

        $newThisMonth = (clone $this->baseQuery($params))
            ->whereBetween('enrollment_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->count();

        return [
            'total' => $this->baseQuery($params)->count(),
            'by_status' => $this->countsByStatus($byStatus, StudentStatus::cases()),
            'new_this_month' => $newThisMonth,
        ];
    }

    /**
     * The filtered, teacher-scoped base query shared by list and summary — no
     * sort, eager-loads or pagination applied.
     */
    private function baseQuery(array $params): Builder
    {
        $query = Student::query();

        // Search: code, name, email, phone, parent name.
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('parents', function ($pq) use ($search) {
                        $pq->where('crm_parents.name', 'like', "%{$search}%");
                    });
            });
        }

        // Filters.
        foreach (['business_id', 'branch_id', 'level_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        // Students enrolled in a given class (via the edu_class_students pivot).
        if (! empty($params['class_id'])) {
            $query->whereIn('id', function ($sub) use ($params) {
                $sub->select('student_id')
                    ->from('edu_class_students')
                    ->where('class_id', $params['class_id'])
                    ->whereNull('deleted_at');
            });
        }

        if (! empty($params['enrolled_from'])) {
            $query->whereDate('enrollment_date', '>=', $params['enrolled_from']);
        }

        if (! empty($params['enrolled_to'])) {
            $query->whereDate('enrollment_date', '<=', $params['enrolled_to']);
        }

        if ($scope = TeacherScope::current()) {
            $scope->constrainStudents($query);
        }

        return $query;
    }

    public function find($id)
    {
        return Student::with(['business', 'branch', 'level', 'profile', 'parents'])->findOrFail($id);
    }

    /**
     * Detail with statistics counts.
     */
    public function detail($id): array
    {
        if ($scope = TeacherScope::current()) {
            $scope->authorizeStudent((int) $id);
        }

        return [
            'student' => $this->find($id),
            'statistics' => $this->statistics($id),
        ];
    }

    public function statistics($id): array
    {
        return [
            'total_enrollments' => $this->countLinked('edu_enrollments', $id, 'student_id'),
            'total_invoices' => $this->countLinked('fin_invoices', $id, 'student_id'),
            'total_exam_results' => $this->countLinked('edu_exam_results', $id, 'student_id'),
        ];
    }

    /**
     * Learning stats for the student detail screen's overview cards/charts
     * (student-detail.md §6.1/§6.2): attendance rate, average exam score,
     * homework completion and per-skill averages. Computed from real
     * attendance/exam/assignment data — everything degrades to 0/null rather
     * than throwing when a table has no rows for this student.
     */
    public function learningStats($id): array
    {
        if ($scope = TeacherScope::current()) {
            $scope->authorizeStudent((int) $id);
        }

        $attendance = $this->guard(
            fn () => DB::table('edu_attendances')
                ->where('student_id', $id)
                ->whereNull('deleted_at')
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as attended")
                ->first(),
            (object) ['total' => 0, 'attended' => 0],
        );

        $totalSessions = (int) ($attendance->total ?? 0);
        $attendanceRate = $totalSessions > 0
            ? (int) round(((int) $attendance->attended / $totalSessions) * 100)
            : 0;

        $exam = $this->guard(
            fn () => DB::table('edu_exam_results')
                ->where('student_id', $id)
                ->selectRaw('AVG(total_score) as avg_total, AVG(listening_score) as avg_listening, AVG(speaking_score) as avg_speaking, AVG(reading_score) as avg_reading, AVG(writing_score) as avg_writing')
                ->first(),
            (object) [],
        );

        $homework = $this->guard(
            fn () => DB::table('edu_assignment_submissions')
                ->where('student_id', $id)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status IN ('submitted', 'late_submitted', 'graded', 'reviewed') THEN 1 ELSE 0 END) as completed")
                ->first(),
            (object) ['total' => 0, 'completed' => 0],
        );

        $homeworkTotal = (int) ($homework->total ?? 0);
        $homeworkCompletion = $homeworkTotal > 0
            ? (int) round(((int) $homework->completed / $homeworkTotal) * 100)
            : 0;

        // edu_exam_results has no per-skill max-score column; skill scores in
        // this schema run up to 25 per skill (4 skills = 100 total), so that's
        // the normalization used for the 0-100 bar. Revisit if a per-exam max
        // is ever added to the schema.
        $skillPercent = fn ($avg) => $avg !== null ? (int) round(min(100, max(0, (float) $avg / 25 * 100))) : 0;

        return [
            'attendance_rate' => $attendanceRate,
            'total_sessions' => $totalSessions,
            'avg_score' => isset($exam->avg_total) && $exam->avg_total !== null ? round((float) $exam->avg_total, 1) : null,
            'homework_completion' => $homeworkCompletion,
            'skills' => [
                'listening' => $skillPercent($exam->avg_listening ?? null),
                'speaking' => $skillPercent($exam->avg_speaking ?? null),
                'reading' => $skillPercent($exam->avg_reading ?? null),
                'writing' => $skillPercent($exam->avg_writing ?? null),
            ],
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $parents = $data['parents'] ?? null;
            $profileData = $this->extractProfile($data);

            $student = new Student($this->extractStudent($data));
            $student->code = $this->generateCode($student->business_id);
            $student->status = Student::STATUS_ACTIVE;
            $student->save();

            if (! empty($profileData)) {
                $student->profile()->create($profileData);
            }

            if (is_array($parents)) {
                $this->syncParents($student, $parents);
            }

            $this->log($student, 'created', null, $student->status);

            event(new StudentCreated($student));

            return $this->find($student->id);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            if ($scope = TeacherScope::current()) {
                $scope->authorizeStudent((int) $id);
            }

            $student = $this->find($id);

            // Immutable: identity & enrollment context (see student.md §3).
            unset($data['id'], $data['code'], $data['business_id'], $data['branch_id'], $data['status']);

            $parents = $data['parents'] ?? null;
            $profileData = $this->extractProfile($data);

            $student->update($this->extractStudent($data));

            if (! empty($profileData)) {
                $student->profile()->updateOrCreate(['student_id' => $student->id], $profileData);
            }

            if (is_array($parents)) {
                $this->syncParents($student, $parents);
            }

            $this->log($student, 'updated');

            return $this->find($student->id);
        });
    }

    /**
     * Move a studying student to "suspended".
     *
     * @throws \RuntimeException when the student is already suspended.
     */
    public function suspend($id, array $data)
    {
        if ($scope = TeacherScope::current()) {
            $scope->authorizeStudent((int) $id);
        }

        $student = $this->find($id);

        if ($student->status === Student::STATUS_SUSPENDED) {
            throw new \RuntimeException('Học viên đang ở trạng thái tạm ngừng.');
        }

        $from = $student->status;
        $student->update(['status' => Student::STATUS_SUSPENDED]);

        $this->log(
            $student,
            'suspended',
            $from,
            Student::STATUS_SUSPENDED,
            $data['reason'] ?? null,
            $data['note'] ?? null,
        );

        return $this->find($student->id);
    }

    /**
     * Return a suspended student to "active".
     *
     * @throws \RuntimeException when the student is not currently suspended.
     */
    public function restore($id, array $data = [])
    {
        if ($scope = TeacherScope::current()) {
            $scope->authorizeStudent((int) $id);
        }

        $student = $this->find($id);

        if ($student->status !== Student::STATUS_SUSPENDED) {
            throw new \RuntimeException('Chỉ có thể khôi phục học viên đang tạm ngừng.');
        }

        $student->update(['status' => Student::STATUS_ACTIVE]);

        $this->log(
            $student,
            'restored',
            Student::STATUS_SUSPENDED,
            Student::STATUS_ACTIVE,
            $data['reason'] ?? null,
        );

        return $this->find($student->id);
    }

    /**
     * Soft delete, blocked when linked data exists.
     *
     * @throws \RuntimeException when related records prevent deletion.
     */
    public function delete($id)
    {
        if ($scope = TeacherScope::current()) {
            $scope->authorizeStudent((int) $id);
        }

        $student = $this->find($id);

        if ($this->hasLinkedData($id, Student::LINKED_TABLES)) {
            throw new \RuntimeException('Không thể xóa Học viên vì đang tồn tại dữ liệu liên quan.');
        }

        return $student->delete();
    }

    /**
     * Exports the filtered student list (e.g. a class roster via `class_id`)
     * as a CSV, stored under the public disk and returned as a link.
     */
    public function export(array $data): array
    {
        $students = $this->baseQuery($data)
            ->with(['branch', 'level'])
            ->orderBy('name')
            ->get();

        $now = now();
        $fileName = "export_student_{$now->getTimestamp()}.csv";
        $relativePath = "assets/export/student/{$fileName}";

        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Mã HV', 'Họ tên', 'Ngày sinh', 'Email', 'Điện thoại', 'Cấp độ', 'Chi nhánh', 'Trạng thái']);

        foreach ($students as $student) {
            fputcsv($handle, [
                $student->code,
                $student->name,
                $student->dob,
                $student->email,
                $student->phone,
                $student->level?->name,
                $student->branch?->name,
                $student->status,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('public')->put($relativePath, $csv);

        return [
            'file_name' => $fileName,
            'created_at' => $now,
            'link' => Storage::disk('public')->url($relativePath),
        ];
    }

    /**
     * Generate the next human-readable student code (e.g. STD000001).
     */
    private function generateCode($businessId): string
    {
        $count = Task::setAndGetReferenceCount('student', $businessId ?? 0);

        return Task::generateReferenceNumber('student', $count, 'STD');
    }

    /**
     * Pull profile-owned keys out of the payload (mutates $data).
     */
    private function extractProfile(array &$data): array
    {
        $profile = [];

        foreach (self::PROFILE_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $profile[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        return $profile;
    }

    /**
     * The student-table attributes left after profile/parents are removed.
     */
    private function extractStudent(array $data): array
    {
        unset($data['parents']);

        return $data;
    }

    /**
     * Attach parents to a student, creating new parent records when no
     * parent_id is supplied. Replaces any existing assignments.
     *
     * @param  array<int, array<string, mixed>>  $parents
     */
    private function syncParents(Student $student, array $parents): void
    {
        $sync = [];

        foreach ($parents as $parent) {
            $parentId = $parent['parent_id'] ?? null;

            if (! $parentId) {
                $parentId = ParentModel::create([
                    'business_id' => $student->business_id,
                    'name' => $parent['name'] ?? null,
                    'phone' => $parent['phone'] ?? null,
                    'email' => $parent['email'] ?? null,
                ])->id;
            }

            $sync[$parentId] = ['relation' => $parent['relation'] ?? null];
        }

        $student->parents()->sync($sync);
    }

    private function log(Student $student, string $action, $from = null, $to = null, $reason = null, $note = null): void
    {
        StudentHistory::create([
            'business_id' => $student->business_id,
            'student_id' => $student->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'note' => $note,
            'created_by' => Auth::guard('api')->id() ?? Auth::id(),
        ]);
    }
}
