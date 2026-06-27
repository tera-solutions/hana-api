<?php

namespace App\Modules\Education\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Dashboard\Actions\GetDashboardSummaryAction;
use App\Modules\Education\Dashboard\Http\Requests\DashboardSummaryRequest;

/**
 * @group Education - Dashboard
 *
 * Aggregated, teacher-scoped dashboard for the Teacher portal.
 *
 * @authenticated
 */
class DashboardController extends Controller
{
    /**
     * Teacher dashboard summary
     *
     * Returns every section the portal dashboard needs (stats, today's & this week's
     * schedule, pending homework, lesson-plan progress, classes and attendance) in a
     * single request. All data is scoped to the authenticated teacher.
     *
     * @queryParam date string Optional Y-m-d anchor for "today" and the ISO week (default = server today). Example: 2026-07-01
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "stats": {"students_enrolled": 72, "active_classes": 12, "lessons_today": 3, "completion_rate": 67},
     *     "schedule_today": [
     *       {"id": 1, "class_id": 10, "class_name": "Starters 2A", "level": null, "room": "Phòng 01", "date": "2026-07-01", "start_time": "08:00", "end_time": "09:30", "status": "upcoming", "lesson_plan_id": 5, "student_count": 24}
     *     ],
     *     "schedule_week": [
     *       {"date": "2026-06-29", "count": 2}, {"date": "2026-06-30", "count": 3}, {"date": "2026-07-01", "count": 3},
     *       {"date": "2026-07-02", "count": 1}, {"date": "2026-07-03", "count": 0}, {"date": "2026-07-04", "count": 0}, {"date": "2026-07-05", "count": 0}
     *     ],
     *     "homework_pending": [{"id": 1, "title": "Unit 01 - Homework", "class_name": "Starters 2A", "pending_count": 5, "deadline": "2026-07-05"}],
     *     "lesson_plans": [{"id": 1, "unit_name": "Kids Starter", "class_name": "IELTS Foundation", "taught_percent": 60}],
     *     "my_classes": [{"id": 10, "name": "Starters 2A", "level": null, "student_count": 24}],
     *     "attendance": {"present": 64, "absent": 5, "late": 3, "total": 72}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function summary(DashboardSummaryRequest $request, GetDashboardSummaryAction $action)
    {
        return $this->respondSuccess($action->handle($request->validated()['date'] ?? null));
    }
}
