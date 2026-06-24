<?php

namespace App\Modules\System\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Task\Http\Requests\CreateTaskAttachmentRequest;
use App\Modules\System\Task\Http\Resources\TaskAttachmentResource;
use App\Modules\System\Task\Services\TaskAttachmentService;

/**
 * @group System - Task Attachment
 *
 * Files attached to a task (task-management.md §VII). Reuse the task permission codes
 * (reads → view, writes → update).
 *
 * @authenticated
 */
class TaskAttachmentController extends Controller
{
    public function __construct(private TaskAttachmentService $service) {}

    /**
     * List attachments
     *
     * @urlParam id integer required The task ID. Example: 1
     */
    public function index($id)
    {
        return $this->respondSuccess(TaskAttachmentResource::collection($this->service->forTask($id)));
    }

    /**
     * Add attachment
     *
     * @urlParam id integer required The task ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thêm tệp đính kèm thành công.", "data": {"id": 1, "file_id": 10}, "code": 200, "errors": null}
     */
    public function create(CreateTaskAttachmentRequest $request, $id)
    {
        return $this->tryRespond(
            fn () => $this->service->create($id, $request->validated()),
            'Thêm tệp đính kèm thành công.',
            fn ($attachment) => new TaskAttachmentResource($attachment),
        );
    }

    /**
     * Delete attachment
     *
     * @urlParam id integer required The attachment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Xóa tệp đính kèm thành công.", "data": null, "code": 200, "errors": null}
     */
    public function delete($id)
    {
        return $this->tryRespond(
            fn () => $this->service->delete($id),
            'Xóa tệp đính kèm thành công.',
            fn () => null,
        );
    }
}
