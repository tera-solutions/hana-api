<?php

namespace App\Modules\Education\Material\Services;

use App\Modules\Education\Material\Models\MaterialCategory;
use Package\Database\Concerns\HandlesEntityQueries;

class MaterialCategoryService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = MaterialCategory::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('category_name', 'like', "%{$search}%")
                    ->orWhere('category_code', 'like', "%{$search}%");
            });
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $this->applySort($query, $params, ['category_name', 'category_code', 'sort_order', 'status', 'created_at']);

        return $query->orderBy('sort_order')->paginate($this->resolvePerPage($params));
    }

    public function create(array $data): MaterialCategory
    {
        return MaterialCategory::create($data);
    }

    public function update($id, array $data): MaterialCategory
    {
        $category = MaterialCategory::findOrFail($id);

        unset($data['id']);
        $category->update($data);

        return $category->fresh();
    }

    public function delete($id): void
    {
        MaterialCategory::findOrFail($id)->delete();
    }
}
