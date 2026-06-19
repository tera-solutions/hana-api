<?php

namespace App\Modules\Education\Question\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Question\Actions\CreateQuestionTagAction;
use App\Modules\Education\Question\Actions\DeleteQuestionTagAction;
use App\Modules\Education\Question\Actions\ListQuestionTagAction;
use App\Modules\Education\Question\Actions\UpdateQuestionTagAction;
use App\Modules\Education\Question\Http\Requests\CreateQuestionTagRequest;
use App\Modules\Education\Question\Http\Requests\UpdateQuestionTagRequest;
use App\Modules\Education\Question\Http\Resources\QuestionTagResource;
use Illuminate\Http\Request;

/**
 * @group Education - Question Tag
 *
 * Manage the question bank tags (question.md §IV, §XV).
 *
 * @authenticated
 */
class QuestionTagController extends Controller
{
    /**
     * List tags
     */
    public function list(Request $request, ListQuestionTagAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), QuestionTagResource::class);
    }

    /**
     * Create tag
     */
    public function create(CreateQuestionTagRequest $request, CreateQuestionTagAction $action)
    {
        return $this->respondSuccess(new QuestionTagResource($action->handle($request->validated())), 'Tạo tag thành công.');
    }

    /**
     * Update tag
     *
     * @urlParam id integer required The tag ID. Example: 1
     */
    public function update(UpdateQuestionTagRequest $request, $id, UpdateQuestionTagAction $action)
    {
        return $this->respondSuccess(new QuestionTagResource($action->handle($id, $request->validated())), 'Cập nhật tag thành công.');
    }

    /**
     * Delete tag
     *
     * @urlParam id integer required The tag ID. Example: 1
     */
    public function delete($id, DeleteQuestionTagAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa tag thành công.');
    }
}
