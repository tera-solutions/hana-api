<?php

namespace App\Modules\CRM\ParentStudent\Services;

use App\Modules\CRM\ParentStudent\Models\ParentStudent;
use Package\Database\Concerns\HandlesEntityQueries;

class ParentStudentService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable, sortable list of relationships.
     */
    public function paginate(array $params = [])
    {
        $query = ParentStudent::query();

        // Search: parent id/name, student id/name/code.
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('parent', function ($pq) use ($search) {
                    $pq->where('crm_parents.id', $search)
                        ->orWhere('crm_parents.name', 'like', "%{$search}%");
                })->orWhereHas('student', function ($sq) use ($search) {
                    $sq->where('edu_students.id', $search)
                        ->orWhere('edu_students.code', 'like', "%{$search}%")
                        ->orWhere('edu_students.name', 'like', "%{$search}%");
                });
            });
        }

        // Pivot-level filters.
        foreach (['relation', 'is_primary_contact', 'is_billing_contact'] as $filter) {
            if (array_key_exists($filter, $params) && $params[$filter] !== '' && $params[$filter] !== null) {
                $query->where($filter, $params[$filter]);
            }
        }

        // Related-model filters.
        if (! empty($params['branch_id']) || ! empty($params['parent_status'])) {
            $query->whereHas('parent', function ($pq) use ($params) {
                if (! empty($params['branch_id'])) {
                    $pq->where('branch_id', $params['branch_id']);
                }
                if (! empty($params['parent_status'])) {
                    $pq->where('status', $params['parent_status']);
                }
            });
        }

        if (! empty($params['student_status'])) {
            $query->whereHas('student', function ($sq) use ($params) {
                $sq->where('status', $params['student_status']);
            });
        }

        $this->applySort($query, $params, ['relation', 'created_at']);

        return $query->with(['parent', 'student'])->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return ParentStudent::with(['parent', 'student'])->findOrFail($id);
    }

    public function create(array $data)
    {
        $link = ParentStudent::create($data);

        return $this->find($link->id);
    }

    public function update($id, array $data)
    {
        $link = $this->find($id);

        // Parent and Student cannot be reassigned (see parent-student.md §2).
        unset($data['id'], $data['parent_id'], $data['student_id']);

        $link->update($data);

        return $this->find($link->id);
    }

    /**
     * Soft delete, blocked when this is the student's last primary contact.
     *
     * @throws \RuntimeException when removing it would leave the student with no
     *                           main contact.
     */
    public function delete($id)
    {
        $link = $this->find($id);

        if ($link->is_primary_contact && ! $this->hasOtherPrimaryContact($link)) {
            throw new \RuntimeException('Không thể xóa vì học viên phải có ít nhất một người liên hệ chính.');
        }

        return $link->delete();
    }

    /**
     * Whether the student has another (non-deleted) primary contact besides this link.
     */
    private function hasOtherPrimaryContact(ParentStudent $link): bool
    {
        return ParentStudent::where('student_id', $link->student_id)
            ->where('id', '!=', $link->id)
            ->where('is_primary_contact', true)
            ->exists();
    }
}
