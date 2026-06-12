<?php

namespace App\Modules\CRM\Parent\Services;

use App\Helpers\Task;
use App\Modules\CRM\Parent\Events\ParentCreated;
use App\Modules\CRM\Parent\Models\ParentHistory;
use App\Modules\CRM\Parent\Models\ParentModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class ParentService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable, sortable list.
     */
    public function paginate(array $params = [])
    {
        $query = ParentModel::query();

        // Search: code, name, email, phone, linked student name/code.
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('students', function ($sq) use ($search) {
                        $sq->where('edu_students.name', 'like', "%{$search}%")
                            ->orWhere('edu_students.code', 'like', "%{$search}%");
                    });
            });
        }

        // Filters.
        foreach (['business_id', 'branch_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        // Filter by relationship type / linked student via the pivot.
        if (! empty($params['relation']) || ! empty($params['student_id'])) {
            $query->whereHas('students', function ($sq) use ($params) {
                if (! empty($params['relation'])) {
                    $sq->where('crm_parent_student.relation', $params['relation']);
                }
                if (! empty($params['student_id'])) {
                    $sq->where('edu_students.id', $params['student_id']);
                }
            });
        }

        if (! empty($params['created_from'])) {
            $query->whereDate('created_at', '>=', $params['created_from']);
        }
        if (! empty($params['created_to'])) {
            $query->whereDate('created_at', '<=', $params['created_to']);
        }

        $this->applySort($query, $params, ['code', 'name', 'created_at']);

        return $query->withCount('students')
            ->with(['business', 'branch'])
            ->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return ParentModel::with(['business', 'branch', 'students'])->findOrFail($id);
    }

    /**
     * Detail with the financial summary aggregated across linked students.
     */
    public function detail($id): array
    {
        return [
            'parent' => $this->find($id),
            'statistics' => $this->statistics($id),
        ];
    }

    public function statistics($id): array
    {
        $studentIds = DB::table('crm_parent_student')->where('parent_id', $id)->pluck('student_id')->all();

        return [
            'total_students' => count($studentIds),
            'total_invoices' => $this->countForStudents('fin_invoices', $studentIds),
            'total_payments' => $this->countForStudents('fin_payments', $studentIds),
            'total_debts' => $this->countForStudents('fin_debts', $studentIds),
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $students = $data['students'] ?? null;

            $parent = new ParentModel($this->withoutStudents($data));
            $parent->code = $this->generateCode($parent->business_id);
            $parent->status = ParentModel::STATUS_ACTIVE;
            $parent->save();

            if (is_array($students)) {
                $this->syncStudents($parent, $students);
            }

            $this->log($parent, 'created', null, $parent->status);

            event(new ParentCreated($parent));

            return $this->find($parent->id);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $parent = $this->find($id);

            // Immutable: identity & enrollment context (see parent.md §3).
            unset($data['id'], $data['code'], $data['business_id'], $data['status']);

            $students = $data['students'] ?? null;

            $parent->update($this->withoutStudents($data));

            if (is_array($students)) {
                $this->syncStudents($parent, $students);
            }

            $this->log($parent, 'updated');

            return $this->find($parent->id);
        });
    }

    /**
     * Move an active parent to "suspended". Student links are preserved.
     *
     * @throws \RuntimeException when the parent is already suspended.
     */
    public function suspend($id, array $data)
    {
        $parent = $this->find($id);

        if ($parent->status === ParentModel::STATUS_SUSPENDED) {
            throw new \RuntimeException('Phụ huynh đang ở trạng thái tạm ngừng.');
        }

        $from = $parent->status;
        $parent->update(['status' => ParentModel::STATUS_SUSPENDED]);

        $this->log(
            $parent,
            'suspended',
            $from,
            ParentModel::STATUS_SUSPENDED,
            $data['reason'] ?? null,
            $data['note'] ?? null,
        );

        return $this->find($parent->id);
    }

    /**
     * Return a suspended parent to "active".
     *
     * @throws \RuntimeException when the parent is not currently suspended.
     */
    public function restore($id, array $data = [])
    {
        $parent = $this->find($id);

        if ($parent->status !== ParentModel::STATUS_SUSPENDED) {
            throw new \RuntimeException('Chỉ có thể khôi phục phụ huynh đang tạm ngừng.');
        }

        $parent->update(['status' => ParentModel::STATUS_ACTIVE]);

        $this->log(
            $parent,
            'restored',
            ParentModel::STATUS_SUSPENDED,
            ParentModel::STATUS_ACTIVE,
            $data['reason'] ?? null,
        );

        return $this->find($parent->id);
    }

    /**
     * Generate the next human-readable parent code (e.g. PAR000001).
     */
    private function generateCode($businessId): string
    {
        $count = Task::setAndGetReferenceCount('parent', $businessId ?? 0);

        return Task::generateReferenceNumber('parent', $count, 'PAR');
    }

    private function withoutStudents(array $data): array
    {
        unset($data['students']);

        return $data;
    }

    /**
     * Attach students to a parent via the crm_parent_student pivot, keeping the
     * relationship type. Replaces any existing links.
     *
     * @param  array<int, array<string, mixed>>  $students
     */
    private function syncStudents(ParentModel $parent, array $students): void
    {
        $sync = [];

        foreach ($students as $student) {
            if (empty($student['student_id'])) {
                continue;
            }

            $sync[$student['student_id']] = ['relation' => $student['relation'] ?? null];
        }

        $parent->students()->sync($sync);
    }

    /**
     * Count rows in a finance table for the given student ids. Missing
     * tables/columns are treated as zero.
     */
    private function countForStudents(string $table, array $studentIds): int
    {
        if (empty($studentIds)) {
            return 0;
        }

        try {
            return (int) DB::table($table)->whereIn('student_id', $studentIds)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function log(ParentModel $parent, string $action, $from = null, $to = null, $reason = null, $note = null): void
    {
        ParentHistory::create([
            'business_id' => $parent->business_id,
            'parent_id' => $parent->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'note' => $note,
            'created_by' => Auth::guard('api')->id() ?? Auth::id(),
        ]);
    }
}
