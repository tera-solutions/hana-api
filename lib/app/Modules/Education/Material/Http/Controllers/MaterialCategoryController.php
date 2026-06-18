<?php

namespace App\Modules\Education\Material\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Material\Actions\CreateMaterialCategoryAction;
use App\Modules\Education\Material\Actions\DeleteMaterialCategoryAction;
use App\Modules\Education\Material\Actions\ListMaterialCategoryAction;
use App\Modules\Education\Material\Actions\UpdateMaterialCategoryAction;
use App\Modules\Education\Material\Http\Requests\CreateMaterialCategoryRequest;
use App\Modules\Education\Material\Http\Requests\UpdateMaterialCategoryRequest;
use App\Modules\Education\Material\Http\Resources\MaterialCategoryResource;
use Illuminate\Http\Request;

/**
 * @group Education - Material Category
 *
 * Document-library taxonomy (material.md §6).
 *
 * @authenticated
 */
class MaterialCategoryController extends Controller
{
    /**
     * List categories
     */
    public function list(Request $request, ListMaterialCategoryAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), MaterialCategoryResource::class);
    }

    /**
     * Create category
     */
    public function create(CreateMaterialCategoryRequest $request, CreateMaterialCategoryAction $action)
    {
        $category = $action->handle($request->validated());

        return $this->respondSuccess(new MaterialCategoryResource($category), 'Tạo danh mục thành công.');
    }

    /**
     * Update category
     *
     * @urlParam id integer required The category ID. Example: 1
     */
    public function update(UpdateMaterialCategoryRequest $request, $id, UpdateMaterialCategoryAction $action)
    {
        $category = $action->handle($id, $request->validated());

        return $this->respondSuccess(new MaterialCategoryResource($category), 'Cập nhật danh mục thành công.');
    }

    /**
     * Delete category
     *
     * @urlParam id integer required The category ID. Example: 1
     */
    public function delete($id, DeleteMaterialCategoryAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa danh mục thành công.');
    }
}
