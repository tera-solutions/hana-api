<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\LessonPlanLesson\Actions\CreateLessonPlanLessonActivityAction;
use App\Modules\Education\LessonPlanLesson\Actions\DeleteLessonPlanLessonActivityAction;
use App\Modules\Education\LessonPlanLesson\Actions\GetLessonPlanLessonActivityAction;
use App\Modules\Education\LessonPlanLesson\Actions\ListLessonPlanLessonActivityAction;
use App\Modules\Education\LessonPlanLesson\Actions\UpdateLessonPlanLessonActivityAction;
use App\Modules\Education\LessonPlanLesson\Http\Requests\CreateLessonPlanLessonActivityRequest;
use App\Modules\Education\LessonPlanLesson\Http\Requests\UpdateLessonPlanLessonActivityRequest;
use App\Modules\Education\LessonPlanLesson\Http\Resources\LessonPlanLessonActivityResource;
use Illuminate\Http\Request;

/**
 * @group Education - Lesson Plan Lessons
 *
 * Manage individual activities (Warm-up/Presentation/Practice/...) within a
 * lesson-plan-lesson template, without resending the whole lesson. Subject to
 * the same editability rule as the lesson itself (draft plan, not linked to a
 * class) — see lesson-plan.md §9, BR004.
 *
 * @authenticated
 */
class LessonPlanLessonActivityController extends Controller
{
    /**
     * List a lesson's activities
     *
     * @queryParam lesson_plan_lesson_id integer Filter by lesson-plan-lesson. Example: 1
     * @queryParam sort_by string Sort column: sort_order, title, created_at (default sort_order). Example: sort_order
     * @queryParam sort_dir string Sort direction: asc or desc (default asc). Example: asc
     * @queryParam per_page integer Page size (default 20). Example: 20
     */
    public function list(Request $request, ListLessonPlanLessonActivityAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), LessonPlanLessonActivityResource::class);
    }

    /**
     * Activity detail
     *
     * @urlParam id integer required The activity ID. Example: 1
     */
    public function detail($id, GetLessonPlanLessonActivityAction $action)
    {
        return $this->respondSuccess(new LessonPlanLessonActivityResource($action->handle($id)));
    }

    /**
     * Create activity
     */
    public function create(CreateLessonPlanLessonActivityRequest $request, CreateLessonPlanLessonActivityAction $action)
    {
        try {
            $activity = $action->handle($request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonPlanLessonActivityResource($activity), 'Thêm hoạt động thành công.');
    }

    /**
     * Update activity
     *
     * @urlParam id integer required The activity ID. Example: 1
     */
    public function update(UpdateLessonPlanLessonActivityRequest $request, $id, UpdateLessonPlanLessonActivityAction $action)
    {
        try {
            $activity = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonPlanLessonActivityResource($activity), 'Cập nhật hoạt động thành công.');
    }

    /**
     * Delete activity
     *
     * @urlParam id integer required The activity ID. Example: 1
     */
    public function delete($id, DeleteLessonPlanLessonActivityAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa hoạt động thành công.');
    }
}
