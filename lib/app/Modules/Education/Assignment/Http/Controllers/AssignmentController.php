<?php

namespace App\Modules\Education\Assignment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Assignment\Actions\AssignByClassAction;
use App\Modules\Education\Assignment\Actions\AssignByGroupAction;
use App\Modules\Education\Assignment\Actions\AssignByLessonAction;
use App\Modules\Education\Assignment\Actions\AssignByStudentAction;
use App\Modules\Education\Assignment\Actions\CreateAssignmentAction;
use App\Modules\Education\Assignment\Actions\DeleteAssignmentAction;
use App\Modules\Education\Assignment\Actions\GetAssignmentAction;
use App\Modules\Education\Assignment\Actions\ListAssignmentAction;
use App\Modules\Education\Assignment\Actions\PublishAssignmentAction;
use App\Modules\Education\Assignment\Actions\SubmitAssignmentAction;
use App\Modules\Education\Assignment\Actions\UpdateAssignmentAction;
use App\Modules\Education\Assignment\Http\Requests\AssignByClassRequest;
use App\Modules\Education\Assignment\Http\Requests\AssignByGroupRequest;
use App\Modules\Education\Assignment\Http\Requests\AssignByLessonRequest;
use App\Modules\Education\Assignment\Http\Requests\AssignByStudentRequest;
use App\Modules\Education\Assignment\Http\Requests\CreateAssignmentRequest;
use App\Modules\Education\Assignment\Http\Requests\SubmitAssignmentRequest;
use App\Modules\Education\Assignment\Http\Requests\UpdateAssignmentRequest;
use App\Modules\Education\Assignment\Http\Resources\AssignmentResource;
use App\Modules\Education\Assignment\Http\Resources\AssignmentSubmissionResource;
use Illuminate\Http\Request;

/**
 * @group Education - Assignment
 *
 * Create, publish, assign, collect and grade student assignments (assignment.md).
 *
 * @authenticated
 */
class AssignmentController extends Controller
{
    /**
     * List assignments
     */
    public function list(Request $request, ListAssignmentAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), AssignmentResource::class);
    }

    /**
     * Assignment detail
     *
     * @urlParam id integer required The assignment ID. Example: 1
     */
    public function detail($id, GetAssignmentAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'assignment' => new AssignmentResource($result['assignment']),
            'progress' => $result['progress'],
        ]);
    }

    /**
     * Create assignment
     */
    public function create(CreateAssignmentRequest $request, CreateAssignmentAction $action)
    {
        $assignment = $action->handle($request->validated());

        return $this->respondSuccess(new AssignmentResource($assignment), 'Tạo bài tập thành công.');
    }

    /**
     * Update assignment
     *
     * @urlParam id integer required The assignment ID. Example: 1
     */
    public function update(UpdateAssignmentRequest $request, $id, UpdateAssignmentAction $action)
    {
        $assignment = $action->handle($id, $request->validated());

        return $this->respondSuccess(new AssignmentResource($assignment), 'Cập nhật bài tập thành công.');
    }

    /**
     * Publish assignment
     *
     * @urlParam id integer required The assignment ID. Example: 1
     */
    public function publish($id, PublishAssignmentAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Giao bài tập thành công.',
            fn ($assignment) => new AssignmentResource($assignment),
        );
    }

    /**
     * Delete assignment
     *
     * @urlParam id integer required The assignment ID. Example: 1
     */
    public function delete($id, DeleteAssignmentAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa bài tập thành công.');
    }

    /**
     * Assign by class
     *
     * Assigns to every active student of a class, seeding an ASSIGNED submission per student (BR004).
     *
     * @urlParam id integer required The assignment ID. Example: 1
     */
    public function assignByClass(AssignByClassRequest $request, $id, AssignByClassAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, (int) $request->validated()['class_room_id']),
            'Giao bài cho lớp học thành công.',
        );
    }

    /**
     * Assign by group (level)
     *
     * Assigns to every active student at a level, seeding an ASSIGNED submission per student (BR004).
     *
     * @urlParam id integer required The assignment ID. Example: 1
     */
    public function assignByGroup(AssignByGroupRequest $request, $id, AssignByGroupAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, (int) $request->validated()['level_id']),
            'Giao bài cho nhóm trình độ thành công.',
        );
    }

    /**
     * Assign by student
     *
     * Assigns to an explicit list of students, seeding an ASSIGNED submission per student (BR004).
     *
     * @urlParam id integer required The assignment ID. Example: 1
     */
    public function assignByStudent(AssignByStudentRequest $request, $id, AssignByStudentAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()['student_ids']),
            'Giao bài cho học viên thành công.',
        );
    }

    /**
     * Assign by lesson
     *
     * Assigns to every active student of the lesson's class, seeding an ASSIGNED submission per student (BR004).
     *
     * @urlParam id integer required The assignment ID. Example: 1
     */
    public function assignByLesson(AssignByLessonRequest $request, $id, AssignByLessonAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, (int) $request->validated()['lesson_id']),
            'Giao bài theo bài học thành công.',
        );
    }

    /**
     * Submit an assignment
     *
     * @urlParam id integer required The assignment ID. Example: 1
     */
    public function submit(SubmitAssignmentRequest $request, $id, SubmitAssignmentAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Nộp bài thành công.',
            fn ($submission) => new AssignmentSubmissionResource($submission),
        );
    }
}
