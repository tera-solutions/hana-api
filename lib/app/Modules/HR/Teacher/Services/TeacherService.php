<?php

namespace App\Modules\HR\Teacher\Services;

use App\Modules\Education\ClassRoom\Models\ClassTeacher;
use App\Modules\HR\Teacher\Events\TeacherCreated;
use App\Modules\HR\Teacher\Models\Contract;
use App\Modules\HR\Teacher\Models\Payroll;
use App\Modules\HR\Teacher\Models\Review;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\HR\Teacher\Models\TeacherHistory;
use App\Modules\HR\Teacher\Models\TeachingSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class TeacherService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable, sortable list.
     */
    public function paginate(array $params = [])
    {
        $query = Teacher::query();

        // Search: full_name, code, email, phone.
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filters.
        foreach (['status', 'teacher_type', 'employment_type', 'branch_id', 'manager_id', 'business_id', 'user_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        // Skill filter (one or many).
        if (! empty($params['skill'])) {
            $skills = (array) $params['skill'];
            $query->whereHas('skills', fn ($sq) => $sq->whereIn('skill_name', $skills));
        }

        if (! empty($params['joined_from'])) {
            $query->whereDate('joined_at', '>=', $params['joined_from']);
        }
        if (! empty($params['joined_to'])) {
            $query->whereDate('joined_at', '<=', $params['joined_to']);
        }

        $this->applySort($query, $params, ['code', 'full_name', 'joined_at', 'created_at', 'status']);

        return $query->with(['branch', 'business', 'skills'])->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return Teacher::with(['user', 'business', 'branch', 'manager', 'skills', 'certificates', 'bankAccount'])->findOrFail($id);
    }

    /**
     * Detail with the operational / rating / payroll statistics.
     */
    public function detail($id): array
    {
        return [
            'teacher' => $this->find($id),
            'statistics' => $this->statistics($id),
        ];
    }

    public function statistics($id): array
    {
        return [
            'total_classes' => $this->guard(fn () => ClassTeacher::where('teacher_id', $id)->count()),
            'total_sessions' => $this->guard(fn () => TeachingSession::where('teacher_id', $id)->count()),
            'total_contracts' => $this->guard(fn () => Contract::where('teacher_id', $id)->count()),
            'total_payrolls' => $this->guard(fn () => Payroll::where('teacher_id', $id)->count()),
            'total_reviews' => $this->guard(fn () => Review::where('teacher_id', $id)->count()),
            'average_rating' => 0, // placeholder until evaluations are modelled
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $skills = $data['skills'] ?? null;
            $bankAccount = $data['bank_account'] ?? null;

            $teacher = new Teacher($this->stripRelations($data));
            $teacher->status = $data['status'] ?? Teacher::STATUS_ACTIVE;
            $teacher->save();

            if (is_array($skills)) {
                $this->syncSkills($teacher, $skills);
            }

            if (is_array($bankAccount)) {
                $this->syncBankAccount($teacher, $bankAccount);
            }

            $this->log($teacher, 'created', null, $teacher->status);

            event(new TeacherCreated($teacher));

            return $this->find($teacher->id);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $teacher = $this->find($id);

            // Code is immutable (teacher.md §4).
            unset($data['id'], $data['code'], $data['status']);

            $skills = $data['skills'] ?? null;
            $bankAccount = $data['bank_account'] ?? null;

            $teacher->update($this->stripRelations($data));

            if (is_array($skills)) {
                $this->syncSkills($teacher, $skills);
            }

            if (is_array($bankAccount)) {
                $this->syncBankAccount($teacher, $bankAccount);
            }

            $this->log($teacher, 'updated');

            return $this->find($teacher->id);
        });
    }

    /**
     * @throws \RuntimeException when already suspended.
     */
    public function suspend($id, array $data)
    {
        $teacher = $this->find($id);

        if ($teacher->status !== Teacher::STATUS_ACTIVE) {
            throw new \RuntimeException('Chỉ có thể tạm ngừng giáo viên đang hoạt động.');
        }

        $from = $teacher->status;
        $teacher->update(['status' => Teacher::STATUS_SUSPENDED]);
        $this->log($teacher, 'suspended', $from, Teacher::STATUS_SUSPENDED, $data['reason'] ?? null);

        return $this->find($teacher->id);
    }

    /**
     * @throws \RuntimeException when not suspended.
     */
    public function restore($id, array $data = [])
    {
        $teacher = $this->find($id);

        if ($teacher->status !== Teacher::STATUS_SUSPENDED) {
            throw new \RuntimeException('Chỉ có thể khôi phục giáo viên đang tạm ngừng.');
        }

        $teacher->update(['status' => Teacher::STATUS_ACTIVE]);
        $this->log($teacher, 'restored', Teacher::STATUS_SUSPENDED, Teacher::STATUS_ACTIVE, $data['reason'] ?? null);

        return $this->find($teacher->id);
    }

    /**
     * Mark a teacher as resigned. Blocked while they still hold classes that must
     * be handed over first (teacher.md §10).
     *
     * @throws \RuntimeException when already resigned or still holding classes.
     */
    public function resign($id, array $data)
    {
        $teacher = $this->find($id);

        if ($teacher->status === Teacher::STATUS_RESIGNED) {
            throw new \RuntimeException('Giáo viên đã nghỉ việc.');
        }

        if ($this->countLinked('edu_class_teacher', $id, 'teacher_id') > 0) {
            throw new \RuntimeException('Giáo viên còn lớp phụ trách, cần chuyển giao trước khi nghỉ việc.');
        }

        $from = $teacher->status;
        $teacher->update([
            'status' => Teacher::STATUS_RESIGNED,
            'resigned_at' => $data['resigned_at'] ?? now()->toDateString(),
        ]);
        $this->log($teacher, 'resigned', $from, Teacher::STATUS_RESIGNED, $data['reason'] ?? null);

        return $this->find($teacher->id);
    }

    private function stripRelations(array $data): array
    {
        unset($data['skills'], $data['bank_account']);

        return $data;
    }

    /**
     * Upsert the teacher's single bank account. Skips when no field is provided.
     *
     * @param  array<string, mixed>  $bank
     */
    private function syncBankAccount(Teacher $teacher, array $bank): void
    {
        $values = [
            'bank_name' => $bank['bank_name'] ?? null,
            'bank_account_number' => $bank['bank_account_number'] ?? null,
            'bank_account_holder' => $bank['bank_account_holder'] ?? null,
            'bank_branch' => $bank['bank_branch'] ?? null,
        ];

        if (! array_filter($values, fn ($v) => $v !== null && $v !== '')) {
            return;
        }

        $teacher->bankAccount()->updateOrCreate([], $values);
    }

    /**
     * @param  array<int, array<string, mixed>|string>  $skills
     */
    private function syncSkills(Teacher $teacher, array $skills): void
    {
        $teacher->skills()->delete();

        foreach ($skills as $skill) {
            $name = is_array($skill) ? ($skill['skill_name'] ?? null) : $skill;

            if (empty($name)) {
                continue;
            }

            $teacher->skills()->create([
                'skill_name' => $name,
                'level' => is_array($skill) ? ($skill['level'] ?? null) : null,
            ]);
        }
    }

    private function log(Teacher $teacher, string $action, $from = null, $to = null, $reason = null, $note = null): void
    {
        TeacherHistory::create([
            'business_id' => $teacher->business_id,
            'teacher_id' => $teacher->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'note' => $note,
            'created_by' => Auth::guard('api')->id() ?? Auth::id(),
        ]);
    }
}
