<?php

namespace App\Modules\System\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Task\Http\Requests\CreateTaskRequest;
use App\Modules\System\Task\Http\Requests\UpdateTaskRequest;
use App\Modules\System\Task\Http\Resources\TaskResource;
use App\Modules\System\Task\Services\TaskService;
use Illuminate\Http\Request;

/**
 * @group System - Task
 *
 * Internal task management: tasks with checklists, comments and attachments
 * (task-management.md). Status changes enforce the checklist/progress gates (BR-02/03)
 * and the assignee/reviewer rules (BR-04/05).
 *
 * @authenticated
 */
class TaskController extends Controller
{
    public function __construct(private TaskService $service) {}

    /**
     * List tasks
     *
     * @queryParam search string Search by code or title. Example: TASK
     * @queryParam status string Filter by status. Example: in_progress
     * @queryParam priority string Filter by priority. Example: high
     * @queryParam category string Filter by category. Example: finance
     * @queryParam assignee_id integer Filter by assignee. Example: 1
     * @queryParam related_type string Filter by linked type. Example: parent
     * @queryParam related_id integer Filter by linked id. Example: 1
     * @queryParam due_from date Due on/after (Y-m-d). Example: 2026-06-01
     * @queryParam due_to date Due on/before (Y-m-d). Example: 2026-06-30
     * @queryParam sort_by string Sort column. Example: due_date
     * @queryParam sort_dir string asc|desc. Example: asc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request)
    {
        return $this->respondPaginated($this->service->paginate($request->all()), TaskResource::class);
    }

    /**
     * Task detail
     *
     * @urlParam id integer required The task ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"id": 1, "task_code": "TASK000001"}, "code": 200, "errors": null}
     */
    public function detail($id)
    {
        return $this->respondSuccess(new TaskResource($this->service->find($id)));
    }

    /**
     * Create task
     *
     * @response 200 {"success": true, "msg": "Tạo công việc thành công.", "data": {"id": 1, "task_code": "TASK000001", "status": "draft"}, "code": 200, "errors": null}
     */
    public function create(CreateTaskRequest $request)
    {
        return $this->tryRespond(
            fn () => $this->service->create($request->validated()),
            'Tạo công việc thành công.',
            fn ($task) => new TaskResource($task),
        );
    }

    /**
     * Update task
     *
     * @urlParam id integer required The task ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Cập nhật công việc thành công.", "data": {"id": 1}, "code": 200, "errors": null}
     * @response 200 scenario="Checklist incomplete" {"success": false, "msg": "Không thể hoàn thành khi checklist chưa hoàn tất.", "data": null, "code": 200, "errors": null}
     */
    public function update(UpdateTaskRequest $request, $id)
    {
        return $this->tryRespond(
            fn () => $this->service->update($id, $request->validated()),
            'Cập nhật công việc thành công.',
            fn ($task) => new TaskResource($task),
        );
    }

    /**
     * Delete task
     *
     * @urlParam id integer required The task ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Xóa công việc thành công.", "data": null, "code": 200, "errors": null}
     */
    public function delete($id)
    {
        return $this->tryRespond(
            fn () => $this->service->delete($id),
            'Xóa công việc thành công.',
            fn () => null,
        );
    }
}
