<?php

namespace App\Modules\Education\ClassSession\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\ClassSession\Actions\CancelSessionAction;
use App\Modules\Education\ClassSession\Actions\CreateSessionAction;
use App\Modules\Education\ClassSession\Actions\DeleteSessionAction;
use App\Modules\Education\ClassSession\Actions\EndSessionAction;
use App\Modules\Education\ClassSession\Actions\GenerateSessionAction;
use App\Modules\Education\ClassSession\Actions\GetSessionAction;
use App\Modules\Education\ClassSession\Actions\ListSessionAction;
use App\Modules\Education\ClassSession\Actions\UpdateSessionAction;
use App\Modules\Education\ClassSession\Http\Requests\CancelSessionRequest;
use App\Modules\Education\ClassSession\Http\Requests\CreateSessionRequest;
use App\Modules\Education\ClassSession\Http\Requests\EndSessionRequest;
use App\Modules\Education\ClassSession\Http\Requests\GenerateSessionRequest;
use App\Modules\Education\ClassSession\Http\Requests\UpdateSessionRequest;
use App\Modules\Education\ClassSession\Http\Resources\ClassSessionResource;
use Illuminate\Http\Request;

/**
 * @group Education - Class Session
 *
 * Manage sessions (buổi học) — the operational unit of a class, linking attendance,
 * teachers, revenue, tags and feedback.
 *
 * @authenticated
 */
class ClassSessionController extends Controller
{
    /**
     * List sessions of a class
     *
     * @urlParam classId integer required The class ID. Example: 1
     *
     * @queryParam search string Search by session name or code. Example: Buổi 1
     * @queryParam status string Filter by status: upcoming, ongoing, completed, cancelled. Example: upcoming
     * @queryParam teacher_id integer Filter by teacher ID. Example: 2
     * @queryParam room_id integer Filter by room ID. Example: 4
     * @queryParam date_from date Sessions on or after (Y-m-d). Example: 2026-07-01
     * @queryParam date_to date Sessions on or before (Y-m-d). Example: 2026-07-31
     * @queryParam tag_ids array Filter by tag IDs. Example: [1,2]
     * @queryParam sort_by string Sort column: session_no, name, session_date, start_time, status, created_at. Example: session_date
     * @queryParam sort_dir string Sort direction: asc or desc. Example: asc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {
     *         "id": 1, "class_id": 1, "session_no": 1, "code": "IELTS-F-2026-07-B01",
     *         "name": "Buổi 1 - Introduction", "session_date": "2026-07-02",
     *         "start_time": "19:00", "end_time": "20:30", "room_id": 4,
     *         "teacher_id": 2, "substitute_teacher_id": null,
     *         "status": "upcoming", "attendance_locked": false, "revenue_amount": "0.00",
     *         "note": null, "tags": []
     *       }
     *     ],
     *     "pagination": {"total": 1, "per_page": 20, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, $classId, ListSessionAction $action)
    {
        $params = array_merge($request->all(), ['class_id' => $classId]);

        return $this->respondPaginated($action->handle($params), ClassSessionResource::class);
    }

    /**
     * Session detail
     *
     * @urlParam id integer required The session ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "id": 1, "class_id": 1, "session_no": 1, "code": "IELTS-F-2026-07-B01",
     *     "name": "Buổi 1 - Introduction", "session_date": "2026-07-02",
     *     "start_time": "19:00", "end_time": "20:30", "status": "upcoming",
     *     "attendance_locked": false, "revenue_amount": "0.00", "tags": []
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetSessionAction $action)
    {
        return $this->respondSuccess(new ClassSessionResource($action->handle($id)));
    }

    /**
     * Create session
     *
     * Creates a single session for the class (spec §5). Rejected when it overlaps
     * another session sharing the class, teacher or room at the same time.
     *
     * @urlParam classId integer required The class ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo buổi học thành công.",
     *   "data": {"id": 1, "class_id": 1, "session_no": 1, "name": "Buổi 1 - Introduction", "status": "upcoming"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Schedule conflict" {
     *   "success": false,
     *   "msg": "Trùng lịch: phòng học, giáo viên hoặc lớp học đã có buổi trong khung giờ này.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateSessionRequest $request, $classId, CreateSessionAction $action)
    {
        try {
            $session = $action->handle(array_merge($request->validated(), ['class_id' => $classId]));
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ClassSessionResource($session), 'Tạo buổi học thành công.');
    }

    /**
     * Generate sessions
     *
     * Bulk-generates sessions from the class schedules over a date range (spec §6).
     * Existing sessions (same class, date and start time) are skipped.
     *
     * @urlParam classId integer required The class ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Sinh buổi học thành công.",
     *   "data": {"created": 24, "skipped": 0},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function generate(GenerateSessionRequest $request, $classId, GenerateSessionAction $action)
    {
        $result = $action->handle($classId, $request->validated());

        return $this->respondSuccess($result, 'Sinh buổi học thành công.');
    }

    /**
     * Update session
     *
     * Partial update of time, teacher, substitute teacher, room, tags or note
     * (spec §7). Blocked once attendance is locked.
     *
     * @urlParam id integer required The session ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật buổi học thành công.",
     *   "data": {"id": 1, "name": "Buổi 1 - Introduction", "status": "upcoming"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Attendance locked" {
     *   "success": false,
     *   "msg": "Buổi học đã chốt điểm danh, không thể cập nhật.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateSessionRequest $request, $id, UpdateSessionAction $action)
    {
        try {
            $session = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ClassSessionResource($session), 'Cập nhật buổi học thành công.');
    }

    /**
     * Cancel session
     *
     * Marks the session as cancelled with a reason (spec §11). No revenue or
     * attendance is recorded.
     *
     * @urlParam id integer required The session ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Hủy buổi học thành công.",
     *   "data": {"id": 1, "status": "cancelled"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Already cancelled" {
     *   "success": false,
     *   "msg": "Buổi học đã được hủy.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function cancel(CancelSessionRequest $request, $id, CancelSessionAction $action)
    {
        try {
            $session = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ClassSessionResource($session), 'Hủy buổi học thành công.');
    }

    /**
     * End session early
     *
     * Marks an in-progress session as completed ahead of its scheduled end time
     * (room-detail.md §6.2 "Dừng lại"). Revenue is kept, unlike cancel().
     *
     * @urlParam id integer required The session ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Kết thúc buổi học thành công.",
     *   "data": {"id": 1, "status": "completed"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Not ongoing" {
     *   "success": false,
     *   "msg": "Chỉ có thể kết thúc sớm buổi học đang diễn ra.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function endSession(EndSessionRequest $request, $id, EndSessionAction $action)
    {
        try {
            $session = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ClassSessionResource($session), 'Kết thúc buổi học thành công.');
    }

    /**
     * Delete session
     *
     * @urlParam id integer required The session ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa buổi học thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function delete($id, DeleteSessionAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa buổi học thành công.');
    }
}
