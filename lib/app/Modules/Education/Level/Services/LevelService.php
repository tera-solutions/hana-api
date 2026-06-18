<?php

namespace App\Modules\Education\Level\Services;

use App\Modules\Education\Level\Models\Level;
use Package\Database\Concerns\HandlesEntityQueries;

class LevelService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable list (student-level.md §XII).
     */
    public function paginate(array $params = [])
    {
        $query = Level::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('level_code', 'like', "%{$search}%")
                    ->orWhere('level_name', 'like', "%{$search}%");
            });
        }

        foreach (['course_id', 'cefr_level', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applySort($query, $params, ['level_code', 'level_name', 'level_order', 'status', 'created_at'], 'level_order');

        return $query->with('course')->paginate($this->resolvePerPage($params));
    }

    public function find($id): Level
    {
        return Level::with('course')->findOrFail($id);
    }

    /**
     * Detail with the count of students currently at this level.
     */
    public function detail($id): array
    {
        return [
            'level' => $this->find($id),
            'statistics' => [
                'students' => $this->countLinked('edu_student_levels', $id, 'level_id'),
            ],
        ];
    }

    public function create(array $data): Level
    {
        $level = new Level($data);
        $level->status = $data['status'] ?? Level::STATUS_ACTIVE;
        $level->save();

        return $this->find($level->id);
    }

    public function update($id, array $data): Level
    {
        $level = Level::findOrFail($id);

        unset($data['id']);

        $level->update($data);

        return $this->find($level->id);
    }

    /**
     * Stop using a level (student-level.md §IV "Ngừng sử dụng cấp độ").
     *
     * @throws \RuntimeException
     */
    public function suspend($id): Level
    {
        $level = Level::findOrFail($id);

        if ($level->status === Level::STATUS_INACTIVE) {
            throw new \RuntimeException('Cấp độ đang ở trạng thái ngừng sử dụng.');
        }

        $level->update(['status' => Level::STATUS_INACTIVE]);

        return $this->find($level->id);
    }

    /**
     * Reactivate a stopped level.
     *
     * @throws \RuntimeException
     */
    public function restore($id): Level
    {
        $level = Level::findOrFail($id);

        if ($level->status === Level::STATUS_ACTIVE) {
            throw new \RuntimeException('Cấp độ đang được sử dụng.');
        }

        $level->update(['status' => Level::STATUS_ACTIVE]);

        return $this->find($level->id);
    }
}
