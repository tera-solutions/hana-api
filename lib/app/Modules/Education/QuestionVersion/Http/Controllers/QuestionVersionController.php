<?php

namespace App\Modules\Education\QuestionVersion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\QuestionVersion\Actions\GetQuestionVersionAction;
use App\Modules\Education\QuestionVersion\Actions\ListQuestionVersionAction;
use App\Modules\Education\QuestionVersion\Http\Resources\QuestionVersionResource;

/**
 * @group Education - Question Versions
 *
 * Browse a question's version history (question.md §IV "Version câu hỏi"). Snapshots are
 * recorded automatically when an in-use question is edited (BR007).
 *
 * @authenticated
 */
class QuestionVersionController extends Controller
{
    /**
     * List versions of a question
     *
     * Returns the question's version history, newest first.
     *
     * @urlParam questionId integer required The question ID. Example: 1
     */
    public function list($questionId, ListQuestionVersionAction $action)
    {
        return $this->respondSuccess(QuestionVersionResource::collection($action->handle($questionId)));
    }

    /**
     * Version detail
     *
     * @urlParam id integer required The version ID. Example: 1
     */
    public function detail($id, GetQuestionVersionAction $action)
    {
        return $this->respondSuccess(new QuestionVersionResource($action->handle($id)));
    }
}
