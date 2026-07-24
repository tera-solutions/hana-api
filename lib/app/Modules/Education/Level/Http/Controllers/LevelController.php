<?php

namespace App\Modules\Education\Level\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Level\Actions\CreateLevelAction;
use App\Modules\Education\Level\Actions\GetLevelAction;
use App\Modules\Education\Level\Actions\ListLevelAction;
use App\Modules\Education\Level\Actions\ReorderLevelAction;
use App\Modules\Education\Level\Actions\RestoreLevelAction;
use App\Modules\Education\Level\Actions\SuspendLevelAction;
use App\Modules\Education\Level\Actions\UpdateLevelAction;
use App\Modules\Education\Level\Http\Requests\CreateLevelRequest;
use App\Modules\Education\Level\Http\Requests\ReorderLevelRequest;
use App\Modules\Education\Level\Http\Requests\UpdateLevelRequest;
use App\Modules\Education\Level\Http\Resources\LevelResource;
use Illuminate\Http\Request;

/**
 * @group Education - Level
 *
 * Level master: the learning-path catalogue of proficiency levels per course
 * (student-level.md §V).
 *
 * @authenticated
 */
class LevelController extends Controller
{
    /**
     * List levels
     *
     * @queryParam search string Match level code or name. Example: Starter
     * @queryParam course_id integer Filter by course. Example: 1
     * @queryParam cefr_level string Filter by CEFR mapping. Example: A1
     * @queryParam status string Filter: active or inactive. Example: active
     */
    public function list(Request $request, ListLevelAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), LevelResource::class);
    }

    /**
     * Level detail
     *
     * @urlParam id integer required The level ID. Example: 1
     */
    public function detail($id, GetLevelAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'level' => new LevelResource($result['level']),
            'statistics' => $result['statistics'],
        ]);
    }

    /**
     * Create level
     */
    public function create(CreateLevelRequest $request, CreateLevelAction $action)
    {
        $level = $action->handle($request->validated());

        return $this->respondSuccess(new LevelResource($level), 'Tạo cấp độ thành công.');
    }

    /**
     * Update level
     *
     * @urlParam id integer required The level ID. Example: 1
     */
    public function update(UpdateLevelRequest $request, $id, UpdateLevelAction $action)
    {
        $level = $action->handle($id, $request->validated());

        return $this->respondSuccess(new LevelResource($level), 'Cập nhật cấp độ thành công.');
    }

    /**
     * Suspend level
     *
     * @urlParam id integer required The level ID. Example: 1
     */
    public function suspend($id, SuspendLevelAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Ngừng sử dụng cấp độ thành công.',
            fn ($level) => new LevelResource($level),
        );
    }

    /**
     * Restore level
     *
     * @urlParam id integer required The level ID. Example: 1
     */
    public function restore($id, RestoreLevelAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Khôi phục cấp độ thành công.',
            fn ($level) => new LevelResource($level),
        );
    }

    /**
     * Reorder levels (drag & drop) — all ids must belong to the same course.
     */
    public function reorder(ReorderLevelRequest $request, ReorderLevelAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($request->validated('order')),
            'Cập nhật thứ tự cấp độ thành công.',
        );
    }
}
