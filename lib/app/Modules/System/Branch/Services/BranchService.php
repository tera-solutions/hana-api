<?php

namespace App\Modules\System\Branch\Services;

use App\Modules\CRM\Parent\Models\ParentModel;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Room\Models\Room;
use App\Modules\Education\Student\Models\Student;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\System\Branch\Events\BranchCreated;
use App\Modules\System\Branch\Models\Branch;
use Package\Database\Concerns\HandlesEntityQueries;

class BranchService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable, sortable list.
     */
    public function paginate(array $params = [])
    {
        $query = Branch::query();

        // Search: code, name, phone, email
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filters
        if (! empty($params['business_id'])) {
            $query->where('business_id', $params['business_id']);
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['province'])) {
            $query->where('province', $params['province']);
        }

        if (! empty($params['manager_id'])) {
            $query->where('manager_id', $params['manager_id']);
        }

        $this->applySort($query, $params, ['code', 'name', 'created_at', 'status']);

        return $query->with(['business', 'manager'])->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return Branch::with(['business', 'manager'])->findOrFail($id);
    }

    /**
     * Detail with statistics counts.
     */
    public function detail($id): array
    {
        $branch = $this->find($id);

        return [
            'branch' => $branch,
            'statistics' => $this->statistics($id),
        ];
    }

    public function statistics($id): array
    {
        return [
            'total_students' => $this->guard(fn () => Student::where('branch_id', $id)->count()),
            'total_parents' => $this->guard(fn () => ParentModel::where('branch_id', $id)->count()),
            'total_teachers' => $this->guard(fn () => Teacher::where('branch_id', $id)->count()),
            'total_classes' => $this->guard(fn () => ClassRoom::where('branch_id', $id)->count()),
            'total_rooms' => $this->guard(fn () => Room::where('branch_id', $id)->count()),
            'total_courses' => $this->guard(fn () => Course::where('branch_id', $id)->count()),
        ];
    }

    public function create(array $data)
    {
        $branch = Branch::create($data);

        event(new BranchCreated($branch));

        return $branch->load(['business', 'manager']);
    }

    public function update($id, array $data)
    {
        $model = $this->find($id);

        // ID, Code and Business are immutable.
        unset($data['id'], $data['code'], $data['business_id']);

        $model->update($data);

        return $model->load(['business', 'manager']);
    }

    /**
     * Soft delete, blocked when linked data exists.
     *
     * @throws \RuntimeException when related records prevent deletion.
     */
    public function delete($id)
    {
        $model = $this->find($id);

        if ($this->hasLinkedData($id, Branch::LINKED_TABLES)) {
            throw new \RuntimeException('Không thể xóa chi nhánh vì đang tồn tại dữ liệu liên quan.');
        }

        return $model->delete();
    }
}
