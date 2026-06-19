<?php

namespace App\Modules\Education\Question\Services;

use App\Modules\Education\Question\Models\QuestionCategory;
use Package\Database\Concerns\HandlesEntityQueries;

class QuestionCategoryService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = QuestionCategory::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('category_code', 'like', "%{$search}%")
                    ->orWhere('category_name', 'like', "%{$search}%");
            });
        }

        if (! empty($params['parent_id'])) {
            $query->where('parent_id', $params['parent_id']);
        }

        $this->applySort($query, $params, ['category_code', 'category_name', 'created_at']);

        return $query->with('parent')->paginate($this->resolvePerPage($params));
    }

    public function create(array $data): QuestionCategory
    {
        return QuestionCategory::create($data);
    }

    public function update($id, array $data): QuestionCategory
    {
        $category = QuestionCategory::findOrFail($id);

        unset($data['id']);

        $category->update($data);

        return $category->fresh('parent');
    }

    public function delete($id): void
    {
        QuestionCategory::findOrFail($id)->delete();
    }
}
