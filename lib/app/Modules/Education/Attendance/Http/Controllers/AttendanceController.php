<?php

namespace App\Modules\Education\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Attendance\Actions\ListAttendanceAction;
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
}
