<?php

namespace App\Modules\CRM\Parent\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Parent\Actions\CreateParentAction;
use App\Modules\CRM\Parent\Actions\GetParentAction;
use App\Modules\CRM\Parent\Actions\ListParentAction;
use App\Modules\CRM\Parent\Actions\RestoreParentAction;
use App\Modules\CRM\Parent\Actions\SuspendParentAction;
use App\Modules\CRM\Parent\Actions\UpdateParentAction;
use App\Modules\CRM\Parent\Http\Requests\CreateParentRequest;
use App\Modules\CRM\Parent\Http\Requests\SuspendParentRequest;
use App\Modules\CRM\Parent\Http\Requests\UpdateParentRequest;
use App\Modules\CRM\Parent\Http\Resources\ParentResource;
use Illuminate\Http\Request;

/**
 * @group CRM - Parent
 *
 * Manage parents / guardians.
 */
class ParentController extends Controller
{
    public function list(Request $request, ListParentAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), ParentResource::class);
    }

    public function detail($id, GetParentAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'parent' => new ParentResource($result['parent']),
            'statistics' => $result['statistics'],
        ]);
    }

    public function create(CreateParentRequest $request, CreateParentAction $action)
    {
        $parent = $action->handle($request->validated());

        return $this->respondSuccess(new ParentResource($parent), 'Tạo phụ huynh thành công.');
    }

    public function update(UpdateParentRequest $request, $id, UpdateParentAction $action)
    {
        $parent = $action->handle($id, $request->validated());

        return $this->respondSuccess(new ParentResource($parent), 'Cập nhật phụ huynh thành công.');
    }

    public function suspend(SuspendParentRequest $request, $id, SuspendParentAction $action)
    {
        try {
            $parent = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ParentResource($parent), 'Tạm ngừng phụ huynh thành công.');
    }

    public function restore($id, RestoreParentAction $action)
    {
        try {
            $parent = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ParentResource($parent), 'Khôi phục phụ huynh thành công.');
    }
}
