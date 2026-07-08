<?php

namespace App\Modules\Education\Room\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Room\Actions\CreateRoomAction;
use App\Modules\Education\Room\Actions\GetRoomAction;
use App\Modules\Education\Room\Actions\ListRoomAction;
use App\Modules\Education\Room\Actions\RestoreRoomAction;
use App\Modules\Education\Room\Actions\SummaryRoomAction;
use App\Modules\Education\Room\Actions\SuspendRoomAction;
use App\Modules\Education\Room\Actions\UpdateRoomAction;
use App\Modules\Education\Room\Http\Requests\CheckRoomScheduleRequest;
use App\Modules\Education\Room\Http\Requests\CreateRoomRequest;
use App\Modules\Education\Room\Http\Requests\SuspendRoomRequest;
use App\Modules\Education\Room\Http\Requests\UpdateRoomRequest;
use App\Modules\Education\Room\Http\Resources\RoomResource;
use App\Modules\Education\Room\Services\RoomService;
use Illuminate\Http\Request;

/**
 * @group Education - Room
 *
 * Manage classrooms (physical rooms) used for scheduling classes and sessions.
 *
 * @authenticated
 */
class RoomController extends Controller
{
    /**
     * List rooms
     *
     * Paginated, filterable list of rooms.
     *
     * @queryParam search string Search by room code or name. Example: A101
     * @queryParam branch_id integer Filter by branch. Example: 1
     * @queryParam room_code string Filter by room code (partial). Example: A1
     * @queryParam room_name string Filter by room name (partial). Example: Phòng
     * @queryParam room_type string Filter by room type. Example: classroom
     * @queryParam status string Filter by status: active, inactive, maintenance. Example: active
     * @queryParam floor string Filter by floor. Example: 1
     * @queryParam sort_by string Sort column: room_code, room_name, capacity, floor, status, created_at. Example: room_code
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: asc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {"id": 1, "room_code": "A101", "room_name": "Phòng A101", "floor": "1", "capacity": 25, "room_type": "classroom", "status": "active", "branch_id": 1, "active_classes_count": 2}
     *     ],
     *     "pagination": {"total": 1, "per_page": 20, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListRoomAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), RoomResource::class);
    }

    /**
     * Room summary
     *
     * Aggregate counts for the room-list dashboard cards.
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"total": 24, "in_use": 18, "maintenance": 2, "empty": 4, "total_students": 480, "online_rooms": 6},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function summary(Request $request, SummaryRoomAction $action)
    {
        return $this->respondSuccess($action->handle($request->all()));
    }

    /**
     * Room detail
     *
     * @urlParam id integer required The room ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "room": {"id": 1, "room_code": "A101", "room_name": "Phòng A101", "capacity": 25, "room_type": "classroom", "status": "active", "branch_id": 1},
     *     "statistics": {"total_classes": 5, "active_classes": 2, "total_sessions": 40, "completed_sessions": 30, "last_used_at": "2026-06-10"},
     *     "classes_in_use": [{"id": 3, "code": "KIDS01", "name": "Kids Basic 01", "teacher_id": 4, "teacher_name": "Nguyễn Văn A", "student_count": 18, "max_students": 20}],
     *     "current_session": {"session_id": 9, "class_id": 3, "class_code": "KIDS01", "class_name": "Kids Basic 01", "course_id": 2, "level": "Starters", "teacher_name": "Nguyễn Văn A", "session_date": "2026-07-07", "start_time": "08:00:00", "end_time": "10:00:00", "class_start_date": "2026-05-06", "class_end_date": "2026-07-30", "student_count": 18, "max_students": 20, "schedules": [{"weekday": 1, "start_time": "08:00:00", "end_time": "10:00:00"}]}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetRoomAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'room' => new RoomResource($result['room']),
            'statistics' => $result['statistics'],
            'classes_in_use' => $result['classes_in_use'],
            'current_session' => $result['current_session'],
        ]);
    }

    /**
     * Create room
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo phòng học thành công.",
     *   "data": {"id": 1, "room_code": "A101", "room_name": "Phòng A101", "capacity": 25, "room_type": "classroom", "status": "active", "branch_id": 1},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateRoomRequest $request, CreateRoomAction $action)
    {
        $room = $action->handle($request->validated());

        return $this->respondSuccess(new RoomResource($room), 'Tạo phòng học thành công.');
    }

    /**
     * Update room
     *
     * @urlParam id integer required The room ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật phòng học thành công.",
     *   "data": {"id": 1, "room_code": "A101", "room_name": "Phòng A101 mới", "capacity": 30, "status": "active", "branch_id": 1},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateRoomRequest $request, $id, UpdateRoomAction $action)
    {
        try {
            $room = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new RoomResource($room), 'Cập nhật phòng học thành công.');
    }

    /**
     * Suspend room
     *
     * @urlParam id integer required The room ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Ngừng sử dụng phòng học thành công.",
     *   "data": {"id": 1, "room_code": "A101", "status": "inactive"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function suspend(SuspendRoomRequest $request, $id, SuspendRoomAction $action)
    {
        try {
            $room = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new RoomResource($room), 'Ngừng sử dụng phòng học thành công.');
    }

    /**
     * Restore room
     *
     * @urlParam id integer required The room ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Khôi phục phòng học thành công.",
     *   "data": {"id": 1, "room_code": "A101", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function restore($id, RestoreRoomAction $action)
    {
        try {
            $room = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new RoomResource($room), 'Khôi phục phòng học thành công.');
    }

    /**
     * Check room schedule conflict
     *
     * Detects sessions in the room that overlap the given slot (room.md §11, BR006).
     *
     * @urlParam id integer required The room ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"has_conflict": true, "conflicts": [{"id": 9, "class_id": 3, "session_date": "2026-07-01", "start_time": "08:00:00", "end_time": "10:00:00"}]},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function schedule(CheckRoomScheduleRequest $request, $id, RoomService $service)
    {
        return $this->respondSuccess($service->checkSchedule($id, $request->validated()));
    }
}
