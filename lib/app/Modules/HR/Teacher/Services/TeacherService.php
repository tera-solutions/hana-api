<?php

namespace App\Modules\HR\Teacher\Services;

use App\Modules\HR\Teacher\Events\TeacherCreated;
use App\Modules\HR\Teacher\Models\Teacher;
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

        // Search: code, name
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filters
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (! empty($params['business_id'])) {
            $query->where('business_id', $params['business_id']);
        }

        if (! empty($params['created_from'])) {
            $query->whereDate('created_at', '>=', $params['created_from']);
        }

        if (! empty($params['created_to'])) {
            $query->whereDate('created_at', '<=', $params['created_to']);
        }

        $this->applySort($query, $params, ['code', 'name', 'created_at', 'status']);

        return $query->with(['user', 'business'])->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return Teacher::with(['user', 'business'])->findOrFail($id);
    }

    /**
     * Detail with statistics counts.
     */
    public function detail($id): array
    {
        $teacher = $this->find($id);

        return [
            'teacher' => $teacher,
            'statistics' => $this->statistics($id),
        ];
    }

    public function statistics($id): array
    {
        return [
            'total_classes' => $this->countLinked('edu_class_teacher', $id, 'teacher_id'),
            'total_sessions' => $this->countLinked('hr_teaching_sessions', $id, 'teacher_id'),
            'total_contracts' => $this->countLinked('hr_contracts', $id, 'teacher_id'),
            'total_payrolls' => $this->countLinked('hr_payrolls', $id, 'teacher_id'),
            'total_reviews' => $this->countLinked('hr_reviews', $id, 'teacher_id'),
        ];
    }

    public function create(array $data)
    {
        $teacher = Teacher::create($data);

        event(new TeacherCreated($teacher));

        return $teacher->load(['user', 'business']);
    }

    public function update($id, array $data)
    {
        $model = $this->find($id);

        // ID and Code are immutable.
        unset($data['id'], $data['code']);

        $model->update($data);

        return $model->load(['user', 'business']);
    }

    /**
     * Soft delete, blocked when linked data exists.
     *
     * @throws \RuntimeException when related records prevent deletion.
     */
    public function delete($id)
    {
        $model = $this->find($id);

        if ($this->hasLinkedData($id, Teacher::LINKED_TABLES)) {
            throw new \RuntimeException('Không thể xóa Teacher vì đang tồn tại dữ liệu liên quan.');
        }

        return $model->delete();
    }
}
