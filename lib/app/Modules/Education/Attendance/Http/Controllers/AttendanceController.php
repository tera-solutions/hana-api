<?php

namespace App\Modules\Education\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Attendance\Actions\CreateAttendanceAction;
use App\Modules\Education\Attendance\Actions\ExportAttendanceAction;
use App\Modules\Education\Attendance\Actions\ListAttendanceAction;
use App\Modules\Education\Attendance\Actions\UpdateAttendanceAction;
use App\Modules\Education\Attendance\Http\Requests\CreateAttendanceRequest;
use App\Modules\Education\Attendance\Http\Requests\ExportAttendanceRequest;
use App\Modules\Education\Attendance\Http\Requests\UpdateAttendanceRequest;
use App\Modules\Education\Attendance\Http\Resources\AttendanceResource;
use Illuminate\Http\Request;

/**
 * @group Education - Attendance
 *
 * Per-student attendance for class sessions (class-session.md §13, §15).
 *
 * @authenticated
 */
class AttendanceController extends Controller
{
    /**
     * List attendance
     *
     * Paginated, filterable attendance records ("Danh sách chuyên cần").
     *
     * @queryParam search string Search by student name or code. Example: Nguyen
     * @queryParam session_id integer Filter by session id. Example: 1
     * @queryParam student_id integer Filter by student id. Example: 1
     * @queryParam class_id integer Filter by the session's class id. Example: 1
     * @queryParam status string Filter: present|absent|late|excused. Example: present
     * @queryParam date date Exact session date (Y-m-d). Example: 2026-06-25
     * @queryParam date_from date Session date on/after (Y-m-d). Example: 2026-06-01
     * @queryParam date_to date Session date on/before (Y-m-d). Example: 2026-06-30
     * @queryParam sort_by string Sort column: status|checkin_time|created_at. Example: created_at
     * @queryParam sort_dir string asc|desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListAttendanceAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), AttendanceResource::class);
    }

    /**
     * Mark attendance
     *
     * One row per (session, student) — re-marking an already-recorded student
     * updates the existing row instead of erroring. Rejected if the session
     * is cancelled or already attendance_locked (spec §7, §11).
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Điểm danh thành công.",
     *   "data": {"id": 1, "session_id": 1, "student_id": 1, "status": "present"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Session cancelled" {
     *   "success": false,
     *   "msg": "Buổi học đã bị hủy, không thể điểm danh.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Attendance locked" {
     *   "success": false,
     *   "msg": "Buổi học đã chốt điểm danh, không thể thay đổi.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateAttendanceRequest $request, CreateAttendanceAction $action)
    {
        try {
            $attendance = $action->handle($request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new AttendanceResource($attendance), 'Điểm danh thành công.');
    }

    /**
     * Update attendance
     *
     * Rejected if the session is cancelled or already attendance_locked
     * (spec §7, §11).
     *
     * @urlParam id integer required The attendance record id. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật điểm danh thành công.",
     *   "data": {"id": 1, "session_id": 1, "student_id": 1, "status": "late"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Attendance locked" {
     *   "success": false,
     *   "msg": "Buổi học đã chốt điểm danh, không thể thay đổi.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateAttendanceRequest $request, $id, UpdateAttendanceAction $action)
    {
        try {
            $attendance = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new AttendanceResource($attendance), 'Cập nhật điểm danh thành công.');
    }

    /**
     * Export attendance
     *
     * Exports the filtered attendance list ("Xuất báo cáo") as a CSV file
     * and returns a downloadable link.
     *
     * @bodyParam class_id integer Filter by the session's class id. Example: 1
     * @bodyParam session_id integer Filter by session id. Example: 1
     * @bodyParam date_from date Session date on/after (Y-m-d). Example: 2026-06-01
     * @bodyParam date_to date Session date on/before (Y-m-d). Example: 2026-06-30
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"file_name": "export_attendance_1776351343.csv", "created_at": "2026-07-09T10:00:00.000000Z", "link": "http://localhost/storage/assets/export/attendance/export_attendance_1776351343.csv"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function export(ExportAttendanceRequest $request, ExportAttendanceAction $action)
    {
        return $this->respondSuccess($action->handle($request->validated()));
    }
}
