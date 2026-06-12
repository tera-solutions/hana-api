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
 *
 * @authenticated
 */
class ParentStudentController extends Controller
{
    /**
     * List parent-student links
     *
     * @queryParam search string Search by parent or student code/name. Example: alice
     * @queryParam relation string Filter by relation (father, mother, ...). Example: father
     * @queryParam is_primary_contact boolean Filter by primary-contact flag. Example: true
     * @queryParam is_billing_contact boolean Filter by billing-contact flag. Example: true
     * @queryParam branch_id integer Filter by the student's branch id. Example: 1
     * @queryParam parent_status string Filter by the parent's status. Example: active
     * @queryParam student_status string Filter by the student's status. Example: active
     * @queryParam sort_by string Sort column: relation, created_at. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {"id": 1, "parent_id": 1, "student_id": 1, "relation": "father", "is_primary_contact": true, "is_billing_contact": true, "is_pickup_authorized": true}
     *     ],
     *     "pagination": {"total": 1, "per_page": 15, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListParentStudentAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), ParentStudentResource::class);
    }

    /**
     * Parent-student link detail
     *
     * @urlParam id integer required The link ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"id": 1, "parent_id": 1, "student_id": 1, "relation": "father", "is_primary_contact": true, "is_billing_contact": true, "is_pickup_authorized": true, "note": null},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetParentStudentAction $action)
    {
        return $this->respondSuccess(new ParentStudentResource($action->handle($id)));
    }

    /**
     * Create parent-student link
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thêm quan hệ học viên thành công.",
     *   "data": {"id": 1, "parent_id": 1, "student_id": 1, "relation": "father", "is_primary_contact": true},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateParentStudentRequest $request, CreateParentStudentAction $action)
    {
        $link = $action->handle($request->validated());

        return $this->respondSuccess(new ParentStudentResource($link), 'Thêm quan hệ học viên thành công.');
    }

    /**
     * Update parent-student link
     *
     * @urlParam id integer required The link ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật quan hệ học viên thành công.",
     *   "data": {"id": 1, "parent_id": 1, "student_id": 1, "relation": "mother", "is_primary_contact": false},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateParentStudentRequest $request, $id, UpdateParentStudentAction $action)
    {
        $link = $action->handle($id, $request->validated());

        return $this->respondSuccess(new ParentStudentResource($link), 'Cập nhật quan hệ học viên thành công.');
    }

    /**
     * Delete parent-student link
     *
     * @urlParam id integer required The link ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa quan hệ học viên thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
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
