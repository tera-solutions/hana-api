<?php

namespace App\Modules\System\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Module\Portal\Repository\NotificationRepository;
use App\Modules\System\Notification\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

/**
 * @group System - Notification
 *
 * Thin `/v1/sys/notification/*` facade over the existing Portal notification
 * repository, reshaped to the standard `{items, pagination}` list envelope
 * used by every other `/v1` module.
 *
 * @authenticated
 */
class NotificationController extends Controller
{
    public function __construct(private NotificationRepository $notifications)
    {
    }

    /**
     * List notifications
     *
     * @queryParam object_id integer Filter by object id. Example: 1
     * @queryParam object_type string Filter by object type. Example: comment
     * @queryParam class_id integer Filter to notifications about a given class. Example: 1
     * @queryParam type string Filter by notification type. Example: assignment
     * @queryParam title string Search by title. Example: Bài tập
     * @queryParam content string Search by content.
     * @queryParam start_date date Created on/after (d/m/Y). Example: 25/06/2026
     * @queryParam end_date date Created on/before (d/m/Y). Example: 30/06/2026
     * @queryParam per_page integer Page size (default 10). Example: 10
     */
    public function list(Request $request)
    {
        return $this->respondPaginated($this->notifications->all($request), NotificationResource::class);
    }

    public function detail($id)
    {
        return $this->respondSuccess(new NotificationResource($this->notifications->find($id)));
    }

    public function create(Request $request)
    {
        return $this->respondSuccess(new NotificationResource($this->notifications->create($request)));
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $input['id'] = $id;

        return $this->respondSuccess(new NotificationResource($this->notifications->update($input)));
    }

    public function delete($id)
    {
        $this->notifications->delete($id);

        return $this->respondSuccess(null, 'Xóa thông báo thành công');
    }

    /**
     * Mark as read for the current user (per-recipient, via `sys_notification_users`)
     * — distinct from `update`, which mutates the shared notification row and is
     * gated behind the messaging feature. Reading your own notifications is not a
     * premium action.
     */
    public function read($id)
    {
        return $this->respondSuccess(new NotificationResource($this->notifications->read($id)));
    }
}
