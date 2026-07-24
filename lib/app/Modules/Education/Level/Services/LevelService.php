<?php

namespace App\Modules\Education\Level\Services;

use App\Modules\Education\Level\Models\Level;
use App\Modules\Education\StudentLevel\Models\StudentLevel;
use Illuminate\Support\Facades\DB;
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

        return $query->with(['course', 'business'])->paginate($this->resolvePerPage($params));
    }

    public function find($id): Level
    {
        return Level::with(['course', 'business'])->findOrFail($id);
    }

    /**
     * Detail with the count of students currently at this level.
     */
    public function detail($id): array
    {
        return [
            'level' => $this->find($id),
            'statistics' => [
                'students' => $this->guard(fn () => StudentLevel::where('level_id', $id)->count()),
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

    /**
     * Drag-and-drop reorder: assigns `level_order` (1-based) from the given
     * id sequence. All ids must belong to the same course.
     *
     * @throws \RuntimeException
     */
    public function reorder(array $orderedIds): void
    {
        $levels = Level::whereIn('id', $orderedIds)->get(['id', 'course_id']);

        if ($levels->count() !== count($orderedIds)) {
            throw new \RuntimeException('Một hoặc nhiều cấp độ không tồn tại.');
        }

        if ($levels->pluck('course_id')->unique()->count() > 1) {
            throw new \RuntimeException('Chỉ có thể sắp xếp các cấp độ trong cùng một khóa học.');
        }

        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                Level::where('id', $id)->update(['level_order' => $index + 1]);
            }
        });
    }
}
