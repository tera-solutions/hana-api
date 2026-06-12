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
 *
 * @authenticated
 */
class BusinessController extends Controller
{
    /**
     * List businesses
     *
     * @queryParam search string Search by business code or name. Example: Hana
     * @queryParam status string Filter by status. Example: active
     * @queryParam manager_id integer Filter by manager user id. Example: 1
     * @queryParam created_from date Created on or after (Y-m-d). Example: 2026-01-01
     * @queryParam created_to date Created on or before (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sort column: business_code, name, created_at, status. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {"id": 1, "business_code": "BIZ001", "name": "Hana English", "short_name": "Hana", "prefix": "HN", "status": "active", "phone": "0900000000", "email": "info@example.com"}
     *     ],
     *     "pagination": {"total": 1, "per_page": 15, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListBusinessAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), BusinessResource::class);
    }

    /**
     * Business detail
     *
     * @urlParam id integer required The business ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "business": {"id": 1, "business_code": "BIZ001", "name": "Hana English", "short_name": "Hana", "status": "active"},
     *     "statistics": {"branches": 3, "users": 25}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetBusinessAction $action)
    {
        $result = $action->handle($id);

        $data = [
            'business' => new BusinessResource($result['business']),
            'statistics' => $result['statistics'],
        ];

        return $this->respondSuccess($data);
    }

    /**
     * Create business
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo Business thành công.",
     *   "data": {"id": 1, "business_code": "BIZ001", "name": "Hana English", "short_name": "Hana", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateBusinessRequest $request, CreateBusinessAction $action)
    {
        $business = $action->handle($request->validated());

        return $this->respondSuccess(new BusinessResource($business), 'Tạo Business thành công.');
    }

    /**
     * Update business
     *
     * @urlParam id integer required The business ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật Business thành công.",
     *   "data": {"id": 1, "business_code": "BIZ001", "name": "Hana English Center", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateBusinessRequest $request, $id, UpdateBusinessAction $action)
    {
        $business = $action->handle($id, $request->validated());

        return $this->respondSuccess(new BusinessResource($business), 'Cập nhật Business thành công.');
    }

    /**
     * Delete business
     *
     * @urlParam id integer required The business ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa Business thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
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
