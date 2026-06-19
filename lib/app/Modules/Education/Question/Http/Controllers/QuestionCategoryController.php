<?php

namespace App\Modules\Education\Question\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Question\Actions\CreateQuestionCategoryAction;
use App\Modules\Education\Question\Actions\DeleteQuestionCategoryAction;
use App\Modules\Education\Question\Actions\ListQuestionCategoryAction;
use App\Modules\Education\Question\Actions\UpdateQuestionCategoryAction;
use App\Modules\Education\Question\Http\Requests\CreateQuestionCategoryRequest;
use App\Modules\Education\Question\Http\Requests\UpdateQuestionCategoryRequest;
use App\Modules\Education\Question\Http\Resources\QuestionCategoryResource;
use Illuminate\Http\Request;

/**
 * @group Education - Question Category
 *
 * Manage the question bank category hierarchy (question.md §XV).
 *
 * @authenticated
 */
class QuestionCategoryController extends Controller
{
    /**
     * List categories
     */
    public function list(Request $request, ListQuestionCategoryAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), QuestionCategoryResource::class);
    }

    /**
     * Create category
     */
    public function create(CreateQuestionCategoryRequest $request, CreateQuestionCategoryAction $action)
    {
        return $this->respondSuccess(new QuestionCategoryResource($action->handle($request->validated())), 'Tạo danh mục thành công.');
    }

    /**
     * Update category
     *
     * @urlParam id integer required The category ID. Example: 1
     */
    public function update(UpdateQuestionCategoryRequest $request, $id, UpdateQuestionCategoryAction $action)
    {
        return $this->respondSuccess(new QuestionCategoryResource($action->handle($id, $request->validated())), 'Cập nhật danh mục thành công.');
    }

    /**
     * Delete category
     *
     * @urlParam id integer required The category ID. Example: 1
     */
    public function delete($id, DeleteQuestionCategoryAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa danh mục thành công.');
    }
}
