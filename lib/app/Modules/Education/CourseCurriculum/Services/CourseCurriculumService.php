<?php

namespace App\Modules\Education\CourseCurriculum\Services;

use App\Modules\Education\Course\Models\CourseCurriculum;
use Package\Database\Concerns\HandlesEntityQueries;

class CourseCurriculumService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable list of a course's curriculum items.
     */
    public function paginate(array $params = [])
    {
        $query = CourseCurriculum::query();

        if (! empty($params['course_id'])) {
            $query->where('course_id', $params['course_id']);
        }

        if (! empty($params['search'])) {
            $query->where('title', 'like', "%{$params['search']}%");
        }

        if (empty($params['sort_by'])) {
            $params['sort_by'] = 'order';
            $params['sort_dir'] = 'asc';
        }
        $this->applySort($query, $params, ['order', 'title', 'created_at'], 'order');

        return $query->with('course')->paginate($this->resolvePerPage($params));
    }

    public function find($id): CourseCurriculum
    {
        return CourseCurriculum::with('course')->findOrFail($id);
    }

    public function create(array $data): CourseCurriculum
    {
        $curriculum = CourseCurriculum::create($data);

        return $this->find($curriculum->id);
    }

    public function update($id, array $data): CourseCurriculum
    {
        $curriculum = $this->find($id);

        // The curriculum item's course cannot be reassigned.
        unset($data['id'], $data['course_id']);

        $curriculum->update($data);

        return $this->find($curriculum->id);
    }

    public function delete($id): void
    {
        $this->find($id)->delete();
    }
}
