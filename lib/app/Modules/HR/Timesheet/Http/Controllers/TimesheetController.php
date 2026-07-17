<?php

namespace App\Modules\HR\Timesheet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\HR\Timesheet\Actions\GetTimesheetSummaryAction;
use App\Modules\HR\Timesheet\Actions\ListTimesheetSessionAction;
use App\Modules\HR\Timesheet\Http\Resources\TimesheetSessionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group HR - Timesheet
 *
 * A teacher's own "công" (worked sessions), derived from `ClassSession` +
 * `Attendance` — no separate check-in/out step. Always scoped to the acting
 * teacher; there is no cross-teacher query here (that belongs to Payroll's
 * admin-generate flow).
 *
 * @authenticated
 */
class TimesheetController extends Controller
{
    /**
     * List worked sessions
     *
     * @queryParam date_from date Session date on/after (Y-m-d). Example: 2026-07-01
     * @queryParam date_to date Session date on/before (Y-m-d). Example: 2026-07-31
     * @queryParam month string Shortcut for a whole month (Y-m). Example: 2026-07
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function list(Request $request, ListTimesheetSessionAction $action)
    {
        $teacherId = $this->actingTeacherId();

        if (! $teacherId) {
            return $this->respondSuccess([
                'items' => [],
                'pagination' => ['total' => 0, 'per_page' => 20, 'current_page' => 1, 'last_page' => 1],
            ]);
        }

        return $this->respondPaginated($action->handle($teacherId, $request->all()), TimesheetSessionResource::class);
    }

    /**
     * Worked-hours summary
     *
     * @queryParam date_from date Session date on/after (Y-m-d). Example: 2026-07-01
     * @queryParam date_to date Session date on/before (Y-m-d). Example: 2026-07-31
     * @queryParam month string Shortcut for a whole month (Y-m). Example: 2026-07
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"total_sessions": 12, "total_hours": 18, "hours_by_type": {"scheduled": 18}, "attendance_rate": 92.5, "average_rating": 4.6}, "code": 200, "errors": null}
     */
    public function summary(Request $request, GetTimesheetSummaryAction $action)
    {
        $teacherId = $this->actingTeacherId();

        if (! $teacherId) {
            return $this->respondSuccess([
                'total_sessions' => 0,
                'total_hours' => 0,
                'hours_by_type' => [],
                'attendance_rate' => null,
                'average_rating' => null,
            ]);
        }

        return $this->respondSuccess($action->handle($teacherId, $request->all()));
    }

    /**
     * The acting user's own `hr_teachers` row — there is no "my teacher profile"
     * endpoint, so it's resolved by `user_id` (mirrors the FE's `useCurrentTeacher`).
     * Null for a non-teaching account (e.g. the tenant owner), not an error.
     */
    private function actingTeacherId(): ?int
    {
        $userId = Auth::guard('api')->id();

        return $userId ? Teacher::where('user_id', $userId)->value('id') : null;
    }
}
