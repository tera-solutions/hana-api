<?php

namespace App\Modules\Education\LessonPlanVersion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\LessonPlanVersion\Actions\GetLessonPlanVersionAction;
use App\Modules\Education\LessonPlanVersion\Actions\ListLessonPlanVersionAction;
use App\Modules\Education\LessonPlanVersion\Http\Resources\LessonPlanVersionResource;

/**
 * @group Education - Lesson Plan Versions
 *
 * Browse the published-version history of a lesson plan (lesson-plan.md §13).
 *
 * @authenticated
 */
class LessonPlanVersionController extends Controller
{
    /**
     * List versions of a plan
     *
     * Returns the plan's version history, newest first.
     *
     * @urlParam planId integer required The lesson plan ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thành công.",
     *   "data": [
     *     {"id": 2, "lesson_plan_id": 1, "version": 2, "change_summary": "Bổ sung buổi học", "published_at": "2026-06-17T00:00:00.000000Z", "published_by": 5},
     *     {"id": 1, "lesson_plan_id": 1, "version": 1, "change_summary": null, "published_at": "2026-06-10T00:00:00.000000Z", "published_by": 5}
     *   ],
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list($planId, ListLessonPlanVersionAction $action)
    {
        $versions = $action->handle($planId);

        return $this->respondSuccess(LessonPlanVersionResource::collection($versions));
    }

    /**
     * Version detail
     *
     * @urlParam id integer required The version ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thành công.",
     *   "data": {"id": 1, "lesson_plan_id": 1, "version": 1, "change_summary": null, "published_at": "2026-06-10T00:00:00.000000Z", "published_by": 5},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetLessonPlanVersionAction $action)
    {
        $version = $action->handle($id);

        return $this->respondSuccess(new LessonPlanVersionResource($version));
    }
}
