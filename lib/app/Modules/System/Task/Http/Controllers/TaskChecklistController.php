<?php

namespace App\Modules\System\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Task\Http\Requests\CreateTaskChecklistRequest;
use App\Modules\System\Task\Http\Requests\UpdateTaskChecklistRequest;
use App\Modules\System\Task\Http\Resources\TaskChecklistResource;
use App\Modules\System\Task\Services\TaskChecklistService;

/**
 * @group System - Task Checklist
 *
 * Checklist items of a task (task-management.md §VII). Reuse the task permission codes
 * (reads → view, writes → update).
 *
 * @authenticated
 */
class TaskChecklistController extends Controller
{
    public function __construct(private TaskChecklistService $service) {}

    /**
     * List checklist items
     *
     * @urlParam id integer required The task ID. Example: 1
     */
    public function index($id)
    {
        return $this->respondSuccess(TaskChecklistResource::collection($this->service->forTask($id)));
    }

    /**
     * Create checklist item
     *
     * @urlParam id integer required The task ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Tạo checklist thành công.", "data": {"id": 1, "is_completed": false}, "code": 200, "errors": null}
     */
    public function create(CreateTaskChecklistRequest $request, $id)
    {
        return $this->tryRespond(
            fn () => $this->service->create($id, $request->validated()),
            'Tạo checklist thành công.',
            fn ($item) => new TaskChecklistResource($item),
        );
    }

    /**
     * Update checklist item
     *
     * @urlParam id integer required The checklist item ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Cập nhật checklist thành công.", "data": {"id": 1, "is_completed": true}, "code": 200, "errors": null}
     */
    public function update(UpdateTaskChecklistRequest $request, $id)
    {
        return $this->tryRespond(
            fn () => $this->service->update($id, $request->validated()),
            'Cập nhật checklist thành công.',
            fn ($item) => new TaskChecklistResource($item),
        );
    }

    /**
     * Delete checklist item
     *
     * @urlParam id integer required The checklist item ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Xóa checklist thành công.", "data": null, "code": 200, "errors": null}
     */
    public function delete($id)
    {
        return $this->tryRespond(
            fn () => $this->service->delete($id),
            'Xóa checklist thành công.',
            fn () => null,
        );
    }
}
