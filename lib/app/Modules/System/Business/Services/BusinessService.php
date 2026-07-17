<?php

namespace App\Modules\System\Business\Services;

use App\Modules\System\Business\Events\BusinessCreated;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Package\Database\Concerns\HandlesEntityQueries;
use Package\Tenancy\TenantContext;

/**
 * Tenant-facing view over Business (/v1/sys/business/*). Business is the tenant
 * root and carries no BusinessScope, so every read here is confined to the
 * acting user's own business — only a platform superadmin sees across tenants.
 * The cross-tenant operator surface lives in the Superadmin module instead.
 */
class BusinessService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable, sortable list.
     */
    public function paginate(array $params = [])
    {
        $query = $this->scopeToVisible(Business::query());

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
        return $this->scopeToVisible(Business::with('manager'))->findOrFail($id);
    }

    /**
     * Confine a Business query to what the acting user may see: everything for a
     * platform superadmin, only their own business otherwise. A tenant user with
     * no business resolves to no rows rather than to an unscoped query.
     */
    private function scopeToVisible(Builder $query): Builder
    {
        if (Auth::guard('api')->user()?->is_superadmin) {
            return $query;
        }

        $businessId = TenantContext::businessId();

        return $businessId === null
            ? $query->whereRaw('1 = 0')
            : $query->whereKey($businessId);
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
