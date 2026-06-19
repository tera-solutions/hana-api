<?php

namespace App\Modules\Education\Question\Services;

use App\Modules\Education\Question\Models\QuestionTag;
use Package\Database\Concerns\HandlesEntityQueries;

class QuestionTagService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = QuestionTag::query();

        if (! empty($params['search'])) {
            $query->where('tag_name', 'like', '%'.$params['search'].'%');
        }

        $this->applySort($query, $params, ['tag_name', 'created_at']);

        return $query->withCount('questions')->paginate($this->resolvePerPage($params));
    }

    public function create(array $data): QuestionTag
    {
        return QuestionTag::create($data);
    }

    public function update($id, array $data): QuestionTag
    {
        $tag = QuestionTag::findOrFail($id);

        unset($data['id']);

        $tag->update($data);

        return $tag->fresh();
    }

    public function delete($id): void
    {
        QuestionTag::findOrFail($id)->delete();
    }
}
