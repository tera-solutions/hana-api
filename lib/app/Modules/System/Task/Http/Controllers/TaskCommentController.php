<?php

namespace App\Modules\System\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Task\Http\Requests\CreateTaskCommentRequest;
use App\Modules\System\Task\Http\Resources\TaskCommentResource;
use App\Modules\System\Task\Services\TaskCommentService;

/**
 * @group System - Task Comment
 *
 * Internal discussion on a task (task-management.md §VII). Reuse the task permission
 * codes (reads → view, writes → update).
 *
 * @authenticated
 */
class TaskCommentController extends Controller
{
    public function __construct(private TaskCommentService $service) {}

    /**
     * List comments
     *
     * @urlParam id integer required The task ID. Example: 1
     */
    public function index($id)
    {
        return $this->respondSuccess(TaskCommentResource::collection($this->service->forTask($id)));
    }

    /**
     * Add comment
     *
     * @urlParam id integer required The task ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thêm bình luận thành công.", "data": {"id": 1, "comment": "Đã liên hệ phụ huynh."}, "code": 200, "errors": null}
     */
    public function create(CreateTaskCommentRequest $request, $id)
    {
        return $this->tryRespond(
            fn () => $this->service->create($id, $request->validated()),
            'Thêm bình luận thành công.',
            fn ($comment) => new TaskCommentResource($comment),
        );
    }
}
