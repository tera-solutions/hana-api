<?php

namespace App\Modules\Education\LeaveRequest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\LeaveRequest\Actions\ApproveLeaveRequestAction;
use App\Modules\Education\LeaveRequest\Actions\CancelLeaveRequestAction;
use App\Modules\Education\LeaveRequest\Actions\CreateLeaveRequestAction;
use App\Modules\Education\LeaveRequest\Actions\GetLeaveRequestAction;
use App\Modules\Education\LeaveRequest\Actions\ListLeaveRequestAction;
use App\Modules\Education\LeaveRequest\Actions\RejectLeaveRequestAction;
use App\Modules\Education\LeaveRequest\Actions\ScheduleMakeupAction;
use App\Modules\Education\LeaveRequest\Actions\UpdateLeaveRequestAction;
use App\Modules\Education\LeaveRequest\Http\Requests\CreateLeaveRequestRequest;
use App\Modules\Education\LeaveRequest\Http\Requests\RejectLeaveRequestRequest;
use App\Modules\Education\LeaveRequest\Http\Requests\ScheduleMakeupRequest;
use App\Modules\Education\LeaveRequest\Http\Requests\UpdateLeaveRequestRequest;
use App\Modules\Education\LeaveRequest\Http\Resources\LeaveRequestResource;
use App\Modules\Education\LeaveRequest\Http\Resources\MakeupLessonResource;
use Illuminate\Http\Request;

/**
 * @group Education - Leave Request
 *
 * Manage student/teacher leave requests, their approval workflow and make-up sessions
 * (leave-request.md).
 *
 * @authenticated
 */
class LeaveRequestController extends Controller
{
    /**
     * List leave requests
     *
     * @queryParam search string Search by request code or reason. Example: LR
     * @queryParam request_type string Filter: student_leave|teacher_leave. Example: student_leave
     * @queryParam requester_type string Filter: student|teacher. Example: student
     * @queryParam requester_id integer Filter by requester id. Example: 1
     * @queryParam class_room_id integer Filter by class id. Example: 1
     * @queryParam status string Filter by status. Example: pending
     * @queryParam leave_date date Exact leave date (Y-m-d). Example: 2026-06-25
     * @queryParam leave_date_from date Leave date on/after (Y-m-d). Example: 2026-06-01
     * @queryParam leave_date_to date Leave date on/before (Y-m-d). Example: 2026-06-30
     * @queryParam sort_by string Sort column. Example: created_at
     * @queryParam sort_dir string asc|desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListLeaveRequestAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), LeaveRequestResource::class);
    }

    /**
     * Leave request detail
     *
     * Returns the request with its lesson, make-up entitlements and status logs.
     *
     * @urlParam id integer required The leave request ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"id": 1, "request_code": "LR000001"}, "code": 200, "errors": null}
     */
    public function detail($id, GetLeaveRequestAction $action)
    {
        return $this->respondSuccess(new LeaveRequestResource($action->handle($id)));
    }

    /**
     * Create leave request
     *
     * @response 200 {"success": true, "msg": "Tạo đơn nghỉ thành công.", "data": {"id": 1, "status": "pending"}, "code": 200, "errors": null}
     * @response 200 scenario="Lesson completed" {"success": false, "msg": "Không thể tạo đơn cho buổi học đã hoàn thành.", "data": null, "code": 200, "errors": null}
     */
    public function create(CreateLeaveRequestRequest $request, CreateLeaveRequestAction $action)
    {
        try {
            $leave = $action->handle($request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LeaveRequestResource($leave), 'Tạo đơn nghỉ thành công.');
    }

    /**
     * Update leave request
     *
     * Only pending requests can be edited.
     *
     * @urlParam id integer required The leave request ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Cập nhật đơn nghỉ thành công.", "data": {"id": 1}, "code": 200, "errors": null}
     */
    public function update(UpdateLeaveRequestRequest $request, $id, UpdateLeaveRequestAction $action)
    {
        try {
            $leave = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LeaveRequestResource($leave), 'Cập nhật đơn nghỉ thành công.');
    }

    /**
     * Approve leave request
     *
     * Approves a pending request; a lesson that already took place cannot be approved
     * (BR010). An approved student leave raises a make-up entitlement (BR007).
     *
     * @urlParam id integer required The leave request ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Duyệt đơn nghỉ thành công.", "data": {"id": 1, "status": "approved"}, "code": 200, "errors": null}
     */
    public function approve($id, ApproveLeaveRequestAction $action)
    {
        try {
            $leave = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LeaveRequestResource($leave), 'Duyệt đơn nghỉ thành công.');
    }

    /**
     * Reject leave request
     *
     * @urlParam id integer required The leave request ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Từ chối đơn nghỉ thành công.", "data": {"id": 1, "status": "rejected"}, "code": 200, "errors": null}
     */
    public function reject(RejectLeaveRequestRequest $request, $id, RejectLeaveRequestAction $action)
    {
        try {
            $leave = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LeaveRequestResource($leave), 'Từ chối đơn nghỉ thành công.');
    }

    /**
     * Cancel leave request
     *
     * @urlParam id integer required The leave request ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Hủy đơn nghỉ thành công.", "data": {"id": 1, "status": "cancelled"}, "code": 200, "errors": null}
     */
    public function cancel($id, CancelLeaveRequestAction $action)
    {
        try {
            $leave = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LeaveRequestResource($leave), 'Hủy đơn nghỉ thành công.');
    }

    /**
     * Schedule make-up session
     *
     * Assigns a make-up lesson to a waiting make-up entitlement (leave-request.md §X).
     *
     * @urlParam makeupId integer required The make-up entitlement ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Xếp lịch học bù thành công.", "data": {"id": 1, "status": "scheduled"}, "code": 200, "errors": null}
     */
    public function scheduleMakeup(ScheduleMakeupRequest $request, $makeupId, ScheduleMakeupAction $action)
    {
        try {
            $makeup = $action->handle($makeupId, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new MakeupLessonResource($makeup), 'Xếp lịch học bù thành công.');
    }
}
