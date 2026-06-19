<?php

namespace App\Modules\Education\Exam\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Exam\Actions\CreateExamSessionAction;
use App\Modules\Education\Exam\Actions\DeleteExamSessionAction;
use App\Modules\Education\Exam\Actions\GetExamSessionAction;
use App\Modules\Education\Exam\Actions\ListExamSessionAction;
use App\Modules\Education\Exam\Actions\RegisterByClassAction;
use App\Modules\Education\Exam\Actions\RegisterByStudentAction;
use App\Modules\Education\Exam\Actions\UpdateExamSessionAction;
use App\Modules\Education\Exam\Http\Requests\CreateExamSessionRequest;
use App\Modules\Education\Exam\Http\Requests\RegisterByClassRequest;
use App\Modules\Education\Exam\Http\Requests\RegisterByStudentRequest;
use App\Modules\Education\Exam\Http\Requests\UpdateExamSessionRequest;
use App\Modules\Education\Exam\Http\Resources\ExamSessionResource;
use Illuminate\Http\Request;

/**
 * @group Education - Exam Session
 *
 * Schedule exam sittings, seat rooms/invigilators and register candidates (exam.md §VIII, §IX).
 *
 * @authenticated
 */
class ExamSessionController extends Controller
{
    /**
     * List exam sessions
     */
    public function list(Request $request, ListExamSessionAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), ExamSessionResource::class);
    }

    /**
     * Exam session detail
     *
     * Returns the sitting with its registration roster and result roster (exam.md §XIV).
     *
     * @urlParam id integer required The exam session ID. Example: 1
     */
    public function detail($id, GetExamSessionAction $action)
    {
        return $this->respondSuccess(new ExamSessionResource($action->handle($id)));
    }

    /**
     * Create exam session
     *
     * Enforces room/invigilator non-conflict (BR001, BR002).
     */
    public function create(CreateExamSessionRequest $request, CreateExamSessionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($request->validated()),
            'Tạo lịch thi thành công.',
            fn ($session) => new ExamSessionResource($session),
        );
    }

    /**
     * Update exam session
     *
     * @urlParam id integer required The exam session ID. Example: 1
     */
    public function update(UpdateExamSessionRequest $request, $id, UpdateExamSessionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Cập nhật lịch thi thành công.',
            fn ($session) => new ExamSessionResource($session),
        );
    }

    /**
     * Delete exam session
     *
     * @urlParam id integer required The exam session ID. Example: 1
     */
    public function delete($id, DeleteExamSessionAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa lịch thi thành công.');
    }

    /**
     * Register a class
     *
     * Auto-registers every active student of a class (BR004; rejected if the sitting is closed, BR005).
     *
     * @urlParam id integer required The exam session ID. Example: 1
     */
    public function registerByClass(RegisterByClassRequest $request, $id, RegisterByClassAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, (int) $request->validated()['class_room_id']),
            'Đăng ký dự thi theo lớp thành công.',
        );
    }

    /**
     * Register students
     *
     * Manually registers a list of students (BR004; rejected if the sitting is closed, BR005).
     *
     * @urlParam id integer required The exam session ID. Example: 1
     */
    public function registerByStudent(RegisterByStudentRequest $request, $id, RegisterByStudentAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()['student_ids']),
            'Đăng ký dự thi theo học viên thành công.',
        );
    }
}
