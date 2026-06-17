<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\LessonPlan\Http\Resources\LessonPlanResource;
use App\Modules\Education\LessonPlanLesson\Actions\AddLessonAction;
use App\Modules\Education\LessonPlanLesson\Actions\DeleteLessonAction;
use App\Modules\Education\LessonPlanLesson\Actions\ReorderLessonsAction;
use App\Modules\Education\LessonPlanLesson\Actions\UpdateLessonAction;
use App\Modules\Education\LessonPlanLesson\Http\Requests\CreateLessonRequest;
use App\Modules\Education\LessonPlanLesson\Http\Requests\ReorderLessonsRequest;
use App\Modules\Education\LessonPlanLesson\Http\Requests\UpdateLessonRequest;
use App\Modules\Education\LessonPlanLesson\Http\Resources\LessonPlanLessonResource;

/**
 * @group Education - Lesson Plan Lessons
 *
 * Manage lesson templates and materials inside a lesson plan.
 *
 * @authenticated
 */
class LessonPlanLessonController extends Controller
{
    /**
     * Add a lesson to a plan
     *
     * @urlParam planId integer required The lesson plan ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thêm buổi học thành công.",
     *   "data": {"id": 1, "lesson_plan_id": 1, "lesson_no": 1, "lesson_title": "Alphabet"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function store(CreateLessonRequest $request, $planId, AddLessonAction $action)
    {
        try {
            $lesson = $action->handle($planId, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonPlanLessonResource($lesson), 'Thêm buổi học thành công.');
    }

    /**
     * Update a lesson
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật buổi học thành công.",
     *   "data": {"id": 1, "lesson_no": 1, "lesson_title": "Alphabet & Numbers"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateLessonRequest $request, $id, UpdateLessonAction $action)
    {
        try {
            $lesson = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonPlanLessonResource($lesson), 'Cập nhật buổi học thành công.');
    }

    /**
     * Delete a lesson
     *
     * Remaining lessons are re-sequenced to stay contiguous.
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa buổi học thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function destroy($id, DeleteLessonAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa buổi học thành công.');
    }

    /**
     * Reorder lessons
     *
     * @urlParam planId integer required The lesson plan ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Sắp xếp buổi học thành công.",
     *   "data": {"id": 1, "total_lessons": 3},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function reorder(ReorderLessonsRequest $request, $planId, ReorderLessonsAction $action)
    {
        try {
            $plan = $action->handle($planId, $request->validated()['lesson_ids']);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonPlanResource($plan), 'Sắp xếp buổi học thành công.');
    }
}
