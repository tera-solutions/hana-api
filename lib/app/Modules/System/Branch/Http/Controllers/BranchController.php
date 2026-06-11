<?php

namespace App\Modules\System\Branch\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Branch\Actions\CreateBranchAction;
use App\Modules\System\Branch\Actions\DeleteBranchAction;
use App\Modules\System\Branch\Actions\GetBranchAction;
use App\Modules\System\Branch\Actions\ListBranchAction;
use App\Modules\System\Branch\Actions\UpdateBranchAction;
use App\Modules\System\Branch\Http\Requests\CreateBranchRequest;
use App\Modules\System\Branch\Http\Requests\UpdateBranchRequest;
use App\Modules\System\Branch\Http\Resources\BranchResource;
use Illuminate\Http\Request;

/**
 * @group System - Branch
 *
 * Manage branches (cơ sở) belonging to a Business.
 */
class BranchController extends Controller
{
    public function list(Request $request, ListBranchAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), BranchResource::class);
    }

    public function detail($id, GetBranchAction $action)
    {
        $result = $action->handle($id);

        $data = [
            'branch' => new BranchResource($result['branch']),
            'statistics' => $result['statistics'],
        ];

        return $this->respondSuccess($data);
    }

    public function create(CreateBranchRequest $request, CreateBranchAction $action)
    {
        $branch = $action->handle($request->validated());

        return $this->respondSuccess(new BranchResource($branch), 'Tạo chi nhánh thành công.');
    }

    public function update(UpdateBranchRequest $request, $id, UpdateBranchAction $action)
    {
        $branch = $action->handle($id, $request->validated());

        return $this->respondSuccess(new BranchResource($branch), 'Cập nhật chi nhánh thành công.');
    }

    public function delete($id, DeleteBranchAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa chi nhánh thành công.');
    }
}
