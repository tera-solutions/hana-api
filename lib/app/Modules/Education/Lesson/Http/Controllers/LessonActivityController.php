<?php

namespace App\Modules\Education\Lesson\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Lesson\Actions\UpdateLessonActivityStatusAction;
use App\Modules\Education\Lesson\Http\Requests\UpdateLessonActivityStatusRequest;
use App\Modules\Education\Lesson\Http\Resources\LessonActivityResource;

/**
 * @group Education - Lesson Activity
 *
 * Manage the status of a single teaching activity within a lesson.
 *
 * @authenticated
 */
class LessonActivityController extends Controller
{
    /**
     * Update a lesson activity's status
     *
     * @urlParam id integer required The lesson activity ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật trạng thái hoạt động thành công.",
     *   "data": {"id": 1, "status": "in_progress"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateLessonActivityStatusRequest $request, $id, UpdateLessonActivityStatusAction $action)
    {
        try {
            $activity = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonActivityResource($activity), 'Cập nhật trạng thái hoạt động thành công.');
    }
}
