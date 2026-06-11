<?php

namespace App\Modules\System\Business\Services;

use App\Modules\System\Business\Events\BusinessCreated;
use App\Modules\System\Business\Models\Business;
use Package\Database\Concerns\HandlesEntityQueries;

class BusinessService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable, sortable list.
     */
    public function paginate(array $params = [])
    {
        $query = Business::query();

        // Search: business_code, name, email, phone
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('business_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filters
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['manager_id'])) {
            $query->where('manager_id', $params['manager_id']);
        }

        if (! empty($params['created_from'])) {
            $query->whereDate('created_at', '>=', $params['created_from']);
        }

        if (! empty($params['created_to'])) {
            $query->whereDate('created_at', '<=', $params['created_to']);
        }

        $this->applySort($query, $params, ['business_code', 'name', 'created_at', 'status']);

        return $query->with('manager')->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return Business::with('manager')->findOrFail($id);
    }

    /**
     * Detail with statistics counts.
     */
    public function detail($id): array
    {
        $business = $this->find($id);

        return [
            'business' => $business,
            'statistics' => $this->statistics($id),
        ];
    }

    public function statistics($id): array
    {
        return [
            'total_students' => $this->countLinked('edu_students', $id, 'business_id'),
            'total_parents' => $this->countLinked('crm_parents', $id, 'business_id'),
            'total_teachers' => $this->countLinked('hr_teachers', $id, 'business_id'),
            'total_courses' => $this->countLinked('edu_courses', $id, 'business_id'),
            'total_classes' => $this->countLinked('edu_classes', $id, 'business_id'),
        ];
    }

    public function create(array $data)
    {
        $business = Business::create($data);

        event(new BusinessCreated($business));

        return $business->load('manager');
    }

    public function update($id, array $data)
    {
        $model = $this->find($id);

        // ID and Business Code are immutable.
        unset($data['id'], $data['business_code']);

        $model->update($data);

        return $model->load('manager');
    }

    /**
     * Soft delete, blocked when linked data exists.
     *
     * @throws \RuntimeException when related records prevent deletion.
     */
    public function delete($id)
    {
        $model = $this->find($id);

        if ($this->hasLinkedData($id, Business::LINKED_TABLES)) {
            throw new \RuntimeException('Không thể xóa Business vì đang tồn tại dữ liệu liên quan.');
        }

        return $model->delete();
    }
}
