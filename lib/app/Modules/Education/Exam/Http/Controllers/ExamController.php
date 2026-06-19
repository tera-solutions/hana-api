<?php

namespace App\Modules\Education\Exam\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Exam\Actions\AddExamQuestionAction;
use App\Modules\Education\Exam\Actions\CloneExamAction;
use App\Modules\Education\Exam\Actions\CreateExamAction;
use App\Modules\Education\Exam\Actions\DeleteExamAction;
use App\Modules\Education\Exam\Actions\DeleteExamQuestionAction;
use App\Modules\Education\Exam\Actions\GetExamAction;
use App\Modules\Education\Exam\Actions\ListExamAction;
use App\Modules\Education\Exam\Actions\UpdateExamAction;
use App\Modules\Education\Exam\Actions\UpdateExamQuestionAction;
use App\Modules\Education\Exam\Http\Requests\CreateExamQuestionRequest;
use App\Modules\Education\Exam\Http\Requests\CreateExamRequest;
use App\Modules\Education\Exam\Http\Requests\UpdateExamQuestionRequest;
use App\Modules\Education\Exam\Http\Requests\UpdateExamRequest;
use App\Modules\Education\Exam\Http\Resources\ExamQuestionResource;
use App\Modules\Education\Exam\Http\Resources\ExamResource;
use Illuminate\Http\Request;

/**
 * @group Education - Exam
 *
 * Manage the exam bank and its questions (exam.md §VI, §VII).
 *
 * @authenticated
 */
class ExamController extends Controller
{
    /**
     * List exams
     */
    public function list(Request $request, ListExamAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), ExamResource::class);
    }

    /**
     * Exam detail
     *
     * @urlParam id integer required The exam ID. Example: 1
     */
    public function detail($id, GetExamAction $action)
    {
        return $this->respondSuccess(new ExamResource($action->handle($id)));
    }

    /**
     * Create exam
     */
    public function create(CreateExamRequest $request, CreateExamAction $action)
    {
        return $this->respondSuccess(new ExamResource($action->handle($request->validated())), 'Tạo bài kiểm tra thành công.');
    }

    /**
     * Update exam
     *
     * Editing an exam already in use (published or with a scheduled session) leaves it
     * untouched and returns a new draft version within its lineage; drafts are updated in
     * place (exam.md §IV "Version đề thi").
     *
     * @urlParam id integer required The exam ID. Example: 1
     */
    public function update(UpdateExamRequest $request, $id, UpdateExamAction $action)
    {
        return $this->respondSuccess(new ExamResource($action->handle($id, $request->validated())), 'Cập nhật bài kiểm tra thành công.');
    }

    /**
     * Delete exam
     *
     * @urlParam id integer required The exam ID. Example: 1
     */
    public function delete($id, DeleteExamAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa bài kiểm tra thành công.');
    }

    /**
     * Clone exam
     *
     * Clones the exam (and its questions) into a brand-new, standalone draft at version 1.
     *
     * @urlParam id integer required The source exam ID. Example: 1
     */
    public function clone($id, CloneExamAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Sao chép bài kiểm tra thành công.',
            fn ($exam) => new ExamResource($exam),
        );
    }

    /**
     * Add a question
     *
     * @urlParam id integer required The exam ID. Example: 1
     */
    public function addQuestion(CreateExamQuestionRequest $request, $id, AddExamQuestionAction $action)
    {
        return $this->respondSuccess(new ExamQuestionResource($action->handle($id, $request->validated())), 'Thêm câu hỏi thành công.');
    }

    /**
     * Update a question
     *
     * @urlParam id integer required The question ID. Example: 1
     */
    public function updateQuestion(UpdateExamQuestionRequest $request, $id, UpdateExamQuestionAction $action)
    {
        return $this->respondSuccess(new ExamQuestionResource($action->handle($id, $request->validated())), 'Cập nhật câu hỏi thành công.');
    }

    /**
     * Delete a question
     *
     * @urlParam id integer required The question ID. Example: 1
     */
    public function deleteQuestion($id, DeleteExamQuestionAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa câu hỏi thành công.');
    }
}
