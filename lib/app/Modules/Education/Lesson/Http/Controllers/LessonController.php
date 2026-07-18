<?php

namespace App\Modules\Education\Lesson\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Lesson\Actions\CancelLessonAction;
use App\Modules\Education\Lesson\Actions\CompleteLessonAction;
use App\Modules\Education\Lesson\Actions\GetLessonAction;
use App\Modules\Education\Lesson\Actions\ListLessonAction;
use App\Modules\Education\Lesson\Actions\LockLessonAction;
use App\Modules\Education\Lesson\Actions\RescheduleLessonAction;
use App\Modules\Education\Lesson\Actions\UnlockLessonAction;
use App\Modules\Education\Lesson\Actions\UpdateLessonAction;
use App\Modules\Education\Lesson\Http\Requests\CancelLessonRequest;
use App\Modules\Education\Lesson\Http\Requests\RescheduleLessonRequest;
use App\Modules\Education\Lesson\Http\Requests\UnlockLessonRequest;
use App\Modules\Education\Lesson\Http\Requests\UpdateLessonRequest;
use App\Modules\Education\Lesson\Http\Resources\LessonResource;
use Illuminate\Http\Request;

/**
 * @group Education - Lesson
 *
 * Manage the actual per-class lessons generated from a lesson plan.
 *
 * @authenticated
 */
class LessonController extends Controller
{
    /**
     * List lessons
     *
     * @queryParam search string Search by lesson title. Example: Family
     * @queryParam class_room_id integer Filter by class. Example: 1
     * @queryParam lesson_plan_id integer Filter by lesson plan. Example: 1
     * @queryParam teacher_id integer Filter by teacher. Example: 1
     * @queryParam room_id integer Filter by room. Example: 1
     * @queryParam branch_id integer Filter by branch (via room). Example: 1
     * @queryParam status string Filter by status. Example: scheduled
     * @queryParam lesson_date date Filter by exact date (Y-m-d). Example: 2026-07-01
     * @queryParam date_from date Lessons on or after (Y-m-d). Example: 2026-07-01
     * @queryParam date_to date Lessons on or before (Y-m-d). Example: 2026-07-31
     * @queryParam sort_by string Sort column: lesson_no, lesson_date, start_time, status, created_at. Example: lesson_date
     * @queryParam per_page integer Page size (default 20). Example: 20
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"items": [{"id": 1, "class_room_id": 1, "lesson_no": 1, "lesson_title": "Alphabet", "lesson_date": "2026-07-01", "status": "scheduled"}], "pagination": {"total": 1, "per_page": 20, "current_page": 1, "last_page": 1}},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListLessonAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), LessonResource::class);
    }

    /**
     * Lesson detail
     *
     * Returns the lesson with teaching content and change history.
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"lesson": {"id": 1, "lesson_no": 1, "lesson_title": "Alphabet", "status": "scheduled", "histories": [], "materials": [{"id": 1, "lesson_plan_lesson_id": 1, "file_id": 3, "material_type": "pdf"}]}},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetLessonAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess(['lesson' => new LessonResource($result['lesson'])]);
    }

    /**
     * Update a lesson
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật buổi học thành công.",
     *   "data": {"id": 1, "teacher_id": 2, "lesson_note": "Học viên tiếp thu tốt.", "status": "confirmed"},
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

        return $this->respondSuccess(new LessonResource($lesson), 'Cập nhật buổi học thành công.');
    }

    /**
     * Reschedule a lesson
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Đổi lịch buổi học thành công.",
     *   "data": {"id": 1, "lesson_date": "2026-07-05", "start_time": "09:00:00", "end_time": "11:00:00"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function reschedule(RescheduleLessonRequest $request, $id, RescheduleLessonAction $action)
    {
        try {
            $lesson = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonResource($lesson), 'Đổi lịch buổi học thành công.');
    }

    /**
     * Cancel a lesson
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Hủy buổi học thành công.",
     *   "data": {"id": 1, "status": "cancelled"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function cancel(CancelLessonRequest $request, $id, CancelLessonAction $action)
    {
        try {
            $lesson = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonResource($lesson), 'Hủy buổi học thành công.');
    }

    /**
     * Manually complete a lesson before its scheduled end time
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Hoàn thành buổi học thành công.",
     *   "data": {"id": 1, "status": "completed"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function complete($id, CompleteLessonAction $action)
    {
        try {
            $lesson = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonResource($lesson), 'Hoàn thành buổi học thành công.');
    }

    /**
     * Lock a lesson
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Khóa buổi học thành công.",
     *   "data": {"id": 1, "status": "locked"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function lock($id, LockLessonAction $action)
    {
        try {
            $lesson = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonResource($lesson), 'Khóa buổi học thành công.');
    }

    /**
     * Unlock a lesson
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Mở khóa buổi học thành công.",
     *   "data": {"id": 1, "status": "completed"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function unlock(UnlockLessonRequest $request, $id, UnlockLessonAction $action)
    {
        try {
            $lesson = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonResource($lesson), 'Mở khóa buổi học thành công.');
    }
}
