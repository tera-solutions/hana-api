<?php

namespace App\Modules\HR\Achievement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Achievement\Actions\GetAchievementProgressAction;
use App\Modules\HR\Achievement\Actions\GetAchievementSummaryAction;
use Illuminate\Http\Request;

/**
 * @group HR - Achievement
 *
 * Teacher-facing career/achievement stats: totals, current-period overview and
 * a session/rating trend, scoped to the authenticated teacher's own record.
 *
 * @authenticated
 */
class AchievementController extends Controller
{
    /**
     * Achievement summary
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "career_stats": {"total_classes": 12, "total_hours": 340.5, "total_students": 180, "rating_rate": 0},
     *     "overview": {"avg_rating": 0, "satisfaction_rate": 0, "sessions_count": 18, "active_classes": 6}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function summary(GetAchievementSummaryAction $action)
    {
        return $this->respondSuccess($action->handle());
    }

    /**
     * Achievement progress trend
     *
     * @queryParam period string week, month or year. Example: month
     */
    public function progress(Request $request, GetAchievementProgressAction $action)
    {
        return $this->respondSuccess($action->handle($request->get('period', 'month')));
    }
}
