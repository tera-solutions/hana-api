<?php

namespace App\Modules\System\Business\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Business\Actions\CreateBusinessAction;
use App\Modules\System\Business\Actions\DeleteBusinessAction;
use App\Modules\System\Business\Actions\GetBusinessAction;
use App\Modules\System\Business\Actions\ListBusinessAction;
use App\Modules\System\Business\Actions\UpdateBusinessAction;
use App\Modules\System\Business\Http\Requests\CreateBusinessRequest;
use App\Modules\System\Business\Http\Requests\UpdateBusinessRequest;
use App\Modules\System\Business\Http\Resources\BusinessResource;
use Illuminate\Http\Request;

/**
 * @group System - Business
 *
 * Manage businesses (centers / branches).
 */
class BusinessController extends Controller
{
    public function list(Request $request, ListBusinessAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), BusinessResource::class);
    }

    public function detail($id, GetBusinessAction $action)
    {
        $result = $action->handle($id);

        $data = [
            'business' => new BusinessResource($result['business']),
            'statistics' => $result['statistics'],
        ];

        return $this->respondSuccess($data);
    }

    public function create(CreateBusinessRequest $request, CreateBusinessAction $action)
    {
        $business = $action->handle($request->validated());

        return $this->respondSuccess(new BusinessResource($business), 'Tạo Business thành công.');
    }

    public function update(UpdateBusinessRequest $request, $id, UpdateBusinessAction $action)
    {
        $business = $action->handle($id, $request->validated());

        return $this->respondSuccess(new BusinessResource($business), 'Cập nhật Business thành công.');
    }

    public function delete($id, DeleteBusinessAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa Business thành công.');
    }
}
