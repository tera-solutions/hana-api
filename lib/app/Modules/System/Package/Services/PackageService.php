<?php

namespace App\Modules\System\Package\Services;

use App\Modules\System\Package\Models\Package;
use Package\Database\Concerns\HandlesEntityQueries;

class PackageService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = Package::query()->where('is_active', true);

        $this->applySort($query, $params, ['name', 'price', 'sort_order']);

        return $query->orderBy('sort_order')->paginate($this->resolvePerPage($params));
    }

    /**
     * Superadmin listing — includes inactive/internal packages (e.g. trial),
     * searchable and filterable by active state.
     */
    public function adminPaginate(array $params = [])
    {
        $query = Package::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('package_code', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('is_active', $params) && $params['is_active'] !== null && $params['is_active'] !== '') {
            $query->where('is_active', filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $this->applySort($query, $params, ['name', 'price', 'sort_order', 'created_at']);

        return $query->orderBy('sort_order')->paginate($this->resolvePerPage($params));
    }

    public function find($id): Package
    {
        return Package::findOrFail($id);
    }

    public function create(array $data): Package
    {
        return Package::create($data);
    }

    public function update($id, array $data): Package
    {
        $package = $this->find($id);

        // Code is the immutable business key.
        unset($data['id'], $data['package_code']);

        $package->update($data);

        return $package;
    }

    public function setActive($id, bool $isActive): Package
    {
        $package = $this->find($id);
        $package->update(['is_active' => $isActive]);

        return $package;
    }
}
