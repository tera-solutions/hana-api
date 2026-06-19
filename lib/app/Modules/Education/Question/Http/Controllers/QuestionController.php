<?php

namespace App\Modules\Education\Question\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Exam\Http\Resources\ExamResource;
use App\Modules\Education\Question\Actions\CloneQuestionAction;
use App\Modules\Education\Question\Actions\CreateQuestionAction;
use App\Modules\Education\Question\Actions\DeleteQuestionAction;
use App\Modules\Education\Question\Actions\GenerateExamAction;
use App\Modules\Education\Question\Actions\GetQuestionAction;
use App\Modules\Education\Question\Actions\ImportQuestionAction;
use App\Modules\Education\Question\Actions\ListQuestionAction;
use App\Modules\Education\Question\Actions\TransitionQuestionAction;
use App\Modules\Education\Question\Actions\UpdateQuestionAction;
use App\Modules\Education\Question\Http\Requests\CreateQuestionRequest;
use App\Modules\Education\Question\Http\Requests\GenerateExamRequest;
use App\Modules\Education\Question\Http\Requests\ImportQuestionRequest;
use App\Modules\Education\Question\Http\Requests\UpdateQuestionRequest;
use App\Modules\Education\Question\Http\Resources\QuestionResource;
use Illuminate\Http\Request;

/**
 * @group Education - Question
 *
 * Question bank: author, review, version and reuse questions, and auto-generate exams
 * from them (question.md).
 *
 * @authenticated
 */
class QuestionController extends Controller
{
    /**
     * List questions
     */
    public function list(Request $request, ListQuestionAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), QuestionResource::class);
    }

    /**
     * Question detail
     *
     * @urlParam id integer required The question ID. Example: 1
     */
    public function detail($id, GetQuestionAction $action)
    {
        return $this->respondSuccess(new QuestionResource($action->handle($id)));
    }

    /**
     * Create question
     */
    public function create(CreateQuestionRequest $request, CreateQuestionAction $action)
    {
        return $this->respondSuccess(new QuestionResource($action->handle($request->validated())), 'Tạo câu hỏi thành công.');
    }

    /**
     * Update question
     *
     * @urlParam id integer required The question ID. Example: 1
     */
    public function update(UpdateQuestionRequest $request, $id, UpdateQuestionAction $action)
    {
        return $this->respondSuccess(new QuestionResource($action->handle($id, $request->validated())), 'Cập nhật câu hỏi thành công.');
    }

    /**
     * Delete question
     *
     * @urlParam id integer required The question ID. Example: 1
     */
    public function delete($id, DeleteQuestionAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa câu hỏi thành công.');
    }

    /**
     * Clone question
     *
     * @urlParam id integer required The source question ID. Example: 1
     */
    public function clone($id, CloneQuestionAction $action)
    {
        return $this->respondSuccess(new QuestionResource($action->handle($id)), 'Sao chép câu hỏi thành công.');
    }

    /**
     * Submit for review
     *
     * @urlParam id integer required The question ID. Example: 1
     */
    public function review($id, TransitionQuestionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, 'review'),
            'Gửi duyệt câu hỏi thành công.',
            fn ($q) => new QuestionResource($q),
        );
    }

    /**
     * Approve question
     *
     * @urlParam id integer required The question ID. Example: 1
     */
    public function approve($id, TransitionQuestionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, 'approve'),
            'Duyệt câu hỏi thành công.',
            fn ($q) => new QuestionResource($q),
        );
    }

    /**
     * Activate question
     *
     * Only ACTIVE questions can be drawn into exams (BR005).
     *
     * @urlParam id integer required The question ID. Example: 1
     */
    public function activate($id, TransitionQuestionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, 'activate'),
            'Kích hoạt câu hỏi thành công.',
            fn ($q) => new QuestionResource($q),
        );
    }

    /**
     * Archive question
     *
     * @urlParam id integer required The question ID. Example: 1
     */
    public function archive($id, TransitionQuestionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, 'archive'),
            'Lưu trữ câu hỏi thành công.',
            fn ($q) => new QuestionResource($q),
        );
    }

    /**
     * Import questions
     *
     * Parses an uploaded spreadsheet (referenced by file_id), persisting valid rows and
     * reporting per-row errors without rolling back the batch (BR008).
     */
    public function import(ImportQuestionRequest $request, ImportQuestionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($request->validated()),
            'Import câu hỏi hoàn tất.',
        );
    }

    /**
     * Generate an exam
     *
     * Draws ACTIVE bank questions by skill/level/difficulty into a new draft exam (BR005, BR009, BR010).
     */
    public function generateExam(GenerateExamRequest $request, GenerateExamAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($request->validated()),
            'Sinh đề thi thành công.',
            fn ($exam) => new ExamResource($exam),
        );
    }
}
