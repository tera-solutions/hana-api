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
 *
 * @authenticated
 */
class BranchController extends Controller
{
    /**
     * List branches
     *
     * @queryParam search string Search by branch code or name. Example: Quận 1
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam status string Filter by status. Example: active
     * @queryParam manager_id integer Filter by manager user id. Example: 1
     * @queryParam province string Filter by province. Example: Ho Chi Minh
     * @queryParam sort_by string Sort column: code, name, created_at, status. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {"id": 1, "business_id": 1, "code": "BR001", "name": "Quận 1", "short_name": "Q1", "status": "active", "phone": "0900000000", "email": "q1@example.com", "address": "12 Lê Lợi"}
     *     ],
     *     "pagination": {"total": 1, "per_page": 15, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListBranchAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), BranchResource::class);
    }

    /**
     * Branch detail
     *
     * @urlParam id integer required The branch ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "branch": {"id": 1, "business_id": 1, "code": "BR001", "name": "Quận 1", "status": "active", "capacity": 200},
     *     "statistics": {"students": 80, "teachers": 12}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetBranchAction $action)
    {
        $result = $action->handle($id);

        $data = [
            'branch' => new BranchResource($result['branch']),
            'statistics' => $result['statistics'],
        ];

        return $this->respondSuccess($data);
    }

    /**
     * Create branch
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo chi nhánh thành công.",
     *   "data": {"id": 1, "business_id": 1, "code": "BR001", "name": "Quận 1", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateBranchRequest $request, CreateBranchAction $action)
    {
        $branch = $action->handle($request->validated());

        return $this->respondSuccess(new BranchResource($branch), 'Tạo chi nhánh thành công.');
    }

    /**
     * Update branch
     *
     * @urlParam id integer required The branch ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật chi nhánh thành công.",
     *   "data": {"id": 1, "business_id": 1, "code": "BR001", "name": "Quận 1 (mới)", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateBranchRequest $request, $id, UpdateBranchAction $action)
    {
        $branch = $action->handle($id, $request->validated());

        return $this->respondSuccess(new BranchResource($branch), 'Cập nhật chi nhánh thành công.');
    }

    /**
     * Delete branch
     *
     * @urlParam id integer required The branch ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa chi nhánh thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
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
