<?php

namespace App\Modules\Education\ClassSessionFeedback\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\ClassSessionFeedback\Actions\CreateClassSessionFeedbackAction;
use App\Modules\Education\ClassSessionFeedback\Actions\ListClassSessionFeedbackAction;
use App\Modules\Education\ClassSessionFeedback\Http\Requests\CreateClassSessionFeedbackRequest;
use App\Modules\Education\ClassSessionFeedback\Http\Resources\ClassSessionFeedbackResource;
use Illuminate\Http\Request;

/**
 * @group Education - Session Feedback
 *
 * Per-student notes for a class session (class-session.md §13, §15). Used by
 * the teaching runtime "Lesson Notes" step to record a note per student for
 * the session currently being taught.
 *
 * @authenticated
 */
class ClassSessionFeedbackController extends Controller
{
    /**
     * List session feedback
     *
     * @queryParam session_id integer Filter by session id. Example: 1
     * @queryParam student_id integer Filter by student id. Example: 1
     * @queryParam class_id integer Filter by the session's class id. Example: 1
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 100
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListClassSessionFeedbackAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), ClassSessionFeedbackResource::class);
    }

    /**
     * Save a per-student note
     *
     * One row per (session, student) — re-saving a note for an
     * already-recorded student updates the existing row instead of erroring.
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Lưu ghi chú thành công.",
     *   "data": {"id": 1, "session_id": 1, "student_id": 1, "rating": 4, "comment": "Tham gia tích cực."},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateClassSessionFeedbackRequest $request, CreateClassSessionFeedbackAction $action)
    {
        $feedback = $action->handle($request->validated());

        return $this->respondSuccess(new ClassSessionFeedbackResource($feedback), 'Lưu ghi chú thành công.');
    }
}
