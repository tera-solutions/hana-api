<?php

namespace App\Modules\CRM\ParentStudent\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\ParentStudent\Actions\CreateParentStudentAction;
use App\Modules\CRM\ParentStudent\Actions\DeleteParentStudentAction;
use App\Modules\CRM\ParentStudent\Actions\GetParentStudentAction;
use App\Modules\CRM\ParentStudent\Actions\ListParentStudentAction;
use App\Modules\CRM\ParentStudent\Actions\UpdateParentStudentAction;
use App\Modules\CRM\ParentStudent\Http\Requests\CreateParentStudentRequest;
use App\Modules\CRM\ParentStudent\Http\Requests\UpdateParentStudentRequest;
use App\Modules\CRM\ParentStudent\Http\Resources\ParentStudentResource;
use Illuminate\Http\Request;

/**
 * @group CRM - Parent Student
 *
 * Manage the parent ↔ student relationships.
 */
class ParentStudentController extends Controller
{
    public function list(Request $request, ListParentStudentAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), ParentStudentResource::class);
    }

    public function detail($id, GetParentStudentAction $action)
    {
        return $this->respondSuccess(new ParentStudentResource($action->handle($id)));
    }

    public function create(CreateParentStudentRequest $request, CreateParentStudentAction $action)
    {
        $link = $action->handle($request->validated());

        return $this->respondSuccess(new ParentStudentResource($link), 'Thêm quan hệ học viên thành công.');
    }

    public function update(UpdateParentStudentRequest $request, $id, UpdateParentStudentAction $action)
    {
        $link = $action->handle($id, $request->validated());

        return $this->respondSuccess(new ParentStudentResource($link), 'Cập nhật quan hệ học viên thành công.');
    }

    public function delete($id, DeleteParentStudentAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa quan hệ học viên thành công.');
    }
}
