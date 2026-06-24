<?php

namespace App\Modules\Education\Timetable\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Timetable\Http\Requests\CreateTimetableRequest;
use App\Modules\Education\Timetable\Http\Requests\UpdateTimetableRequest;
use App\Modules\Education\Timetable\Http\Resources\TimetableResource;
use App\Modules\Education\Timetable\Http\Resources\TimetableSessionResource;
use App\Modules\Education\Timetable\Services\TimetableService;
use Illuminate\Http\Request;

/**
 * @group Education - Timetable
 *
 * Class schedules that generate sessions, with conflict checks (BR-01/02/03) and
 * per-teacher / per-student / per-room calendar views (timetable-management.md).
 *
 * @authenticated
 */
class TimetableController extends Controller
{
    public function __construct(private TimetableService $service) {}

    /**
     * List timetables
     *
     * @queryParam search string Search by code or name. Example: TKB
     * @queryParam class_room_id integer Filter by class. Example: 1
     * @queryParam teacher_id integer Filter by teacher. Example: 1
     * @queryParam room_id integer Filter by room. Example: 1
     * @queryParam course_id integer Filter by course. Example: 1
     * @queryParam status string Filter: draft|active|completed|cancelled. Example: active
     * @queryParam sort_by string Sort column. Example: start_date
     * @queryParam sort_dir string asc|desc. Example: asc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request)
    {
        return $this->respondPaginated($this->service->paginate($request->all()), TimetableResource::class);
    }

    /**
     * Timetable detail
     *
     * Returns the timetable with its rules and generated sessions.
     *
     * @urlParam id integer required The timetable ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"id": 1, "timetable_code": "TKB000001"}, "code": 200, "errors": null}
     */
    public function detail($id)
    {
        return $this->respondSuccess(new TimetableResource($this->service->find($id)));
    }

    /**
     * Create timetable
     *
     * Generates the class sessions from the schedule rules; rejects room/teacher clashes
     * (BR-01/02) and over-capacity rooms (BR-03).
     *
     * @response 200 {"success": true, "msg": "Tạo thời khóa biểu thành công.", "data": {"id": 1, "timetable_code": "TKB000001", "total_sessions": 20}, "code": 200, "errors": null}
     * @response 200 scenario="Room clash" {"success": false, "msg": "Phòng học đã có lịch trùng vào 2026-07-01 18:00:00.", "data": null, "code": 200, "errors": null}
     */
    public function create(CreateTimetableRequest $request)
    {
        return $this->tryRespond(
            fn () => $this->service->create($request->validated()),
            'Tạo thời khóa biểu thành công.',
            fn ($timetable) => new TimetableResource($timetable),
        );
    }

    /**
     * Update timetable
     *
     * @urlParam id integer required The timetable ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Cập nhật thời khóa biểu thành công.", "data": {"id": 1}, "code": 200, "errors": null}
     */
    public function update(UpdateTimetableRequest $request, $id)
    {
        return $this->tryRespond(
            fn () => $this->service->update($id, $request->validated()),
            'Cập nhật thời khóa biểu thành công.',
            fn ($timetable) => new TimetableResource($timetable),
        );
    }

    /**
     * Delete timetable
     *
     * @urlParam id integer required The timetable ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Xóa thời khóa biểu thành công.", "data": null, "code": 200, "errors": null}
     */
    public function delete($id)
    {
        return $this->tryRespond(
            fn () => $this->service->delete($id),
            'Xóa thời khóa biểu thành công.',
            fn () => null,
        );
    }

    /**
     * Calendar
     *
     * Sessions in a date range (day / week / month), optionally scoped by class, teacher or room.
     *
     * @queryParam date_from date required Range start (Y-m-d). Example: 2026-07-01
     * @queryParam date_to date required Range end (Y-m-d). Example: 2026-07-31
     * @queryParam class_id integer Filter by class. Example: 1
     * @queryParam teacher_id integer Filter by teacher. Example: 1
     * @queryParam room_id integer Filter by room. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": [], "code": 200, "errors": null}
     */
    public function calendar(Request $request)
    {
        return $this->respondSuccess(TimetableSessionResource::collection($this->service->calendar($request->all())));
    }

    /**
     * Teacher schedule
     *
     * @urlParam id integer required The teacher ID. Example: 1
     *
     * @queryParam date_from date Range start (Y-m-d). Example: 2026-07-01
     * @queryParam date_to date Range end (Y-m-d). Example: 2026-07-31
     */
    public function teacherSchedule(Request $request, $id)
    {
        return $this->respondSuccess(TimetableSessionResource::collection($this->service->teacherSchedule($id, $request->all())));
    }

    /**
     * Student schedule
     *
     * @urlParam id integer required The student ID. Example: 1
     *
     * @queryParam date_from date Range start (Y-m-d). Example: 2026-07-01
     * @queryParam date_to date Range end (Y-m-d). Example: 2026-07-31
     */
    public function studentSchedule(Request $request, $id)
    {
        return $this->respondSuccess(TimetableSessionResource::collection($this->service->studentSchedule($id, $request->all())));
    }

    /**
     * Room schedule
     *
     * @urlParam id integer required The room ID. Example: 1
     *
     * @queryParam date_from date Range start (Y-m-d). Example: 2026-07-01
     * @queryParam date_to date Range end (Y-m-d). Example: 2026-07-31
     */
    public function roomSchedule(Request $request, $id)
    {
        return $this->respondSuccess(TimetableSessionResource::collection($this->service->roomSchedule($id, $request->all())));
    }
}
