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
 *
 * @authenticated
 */
class ParentController extends Controller
{
    /**
     * List parents
     *
     * @queryParam search string Search by parent code, name, email or phone. Example: robert
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam branch_id integer Filter by branch id. Example: 1
     * @queryParam status string Filter by status. Example: active
     * @queryParam relation string Filter by relation to a student (father, mother, ...). Example: father
     * @queryParam student_id integer Filter parents linked to this student id. Example: 1
     * @queryParam created_from date Created on or after (Y-m-d). Example: 2026-01-01
     * @queryParam created_to date Created on or before (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sort column: code, name, created_at. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {"id": 1, "code": "PAR001", "name": "Trần Thị B", "gender": "female", "email": "b@example.com", "phone": "0900000000", "status": "active", "business_id": 1, "branch_id": 1}
     *     ],
     *     "pagination": {"total": 1, "per_page": 15, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListParentAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), ParentResource::class);
    }

    /**
     * Parent detail
     *
     * @urlParam id integer required The parent ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "parent": {"id": 1, "code": "PAR001", "name": "Trần Thị B", "phone": "0900000000", "email": "b@example.com", "status": "active"},
     *     "statistics": {"students": 2}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetParentAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'parent' => new ParentResource($result['parent']),
            'statistics' => $result['statistics'],
        ]);
    }

    /**
     * Create parent
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo phụ huynh thành công.",
     *   "data": {"id": 1, "code": "PAR001", "name": "Trần Thị B", "phone": "0900000000", "email": "b@example.com", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateParentRequest $request, CreateParentAction $action)
    {
        $parent = $action->handle($request->validated());

        return $this->respondSuccess(new ParentResource($parent), 'Tạo phụ huynh thành công.');
    }

    /**
     * Update parent
     *
     * @urlParam id integer required The parent ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật phụ huynh thành công.",
     *   "data": {"id": 1, "code": "PAR001", "name": "Trần Thị B", "phone": "0900000001", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateParentRequest $request, $id, UpdateParentAction $action)
    {
        $parent = $action->handle($id, $request->validated());

        return $this->respondSuccess(new ParentResource($parent), 'Cập nhật phụ huynh thành công.');
    }

    /**
     * Suspend parent
     *
     * @urlParam id integer required The parent ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạm ngừng phụ huynh thành công.",
     *   "data": {"id": 1, "code": "PAR001", "name": "Trần Thị B", "status": "suspended"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function suspend(SuspendParentRequest $request, $id, SuspendParentAction $action)
    {
        try {
            $parent = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ParentResource($parent), 'Tạm ngừng phụ huynh thành công.');
    }

    /**
     * Restore parent
     *
     * @urlParam id integer required The parent ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Khôi phục phụ huynh thành công.",
     *   "data": {"id": 1, "code": "PAR001", "name": "Trần Thị B", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
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
