<?php

namespace App\Modules\CRM\Lead\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Lead\Actions\CreateLeadGuardianAction;
use App\Modules\CRM\Lead\Actions\DeleteLeadGuardianAction;
use App\Modules\CRM\Lead\Actions\ListLeadGuardianAction;
use App\Modules\CRM\Lead\Actions\UpdateLeadGuardianAction;
use App\Modules\CRM\Lead\Http\Requests\CreateLeadGuardianRequest;
use App\Modules\CRM\Lead\Http\Requests\UpdateLeadGuardianRequest;
use App\Modules\CRM\Lead\Http\Resources\LeadGuardianResource;
use Illuminate\Http\Request;

/**
 * @group CRM - Lead Guardians
 *
 * Manage the guardians of a lead (lead.md §8 "Quản lý Người giám hộ").
 *
 * @authenticated
 */
class LeadGuardianController extends Controller
{
    /**
     * List guardians for a lead
     *
     * @urlParam leadId integer required The lead ID. Example: 1
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {
     *         "id": 1, "lead_id": 1,
     *         "full_name": "Nguyễn Văn B", "relationship": "Bố", "phone": "0907654321", "email": null,
     *         "created_by": 1, "updated_by": null, "deleted_by": null,
     *         "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-01T08:00:00.000000Z", "deleted_at": null
     *       }
     *     ],
     *     "pagination": {"total": 1, "per_page": 20, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list($leadId, Request $request, ListLeadGuardianAction $action)
    {
        return $this->respondPaginated($action->handle($leadId, $request->all()), LeadGuardianResource::class);
    }

    /**
     * Add guardian to a lead
     *
     * @urlParam leadId integer required The lead ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thêm người giám hộ thành công.",
     *   "data": {
     *     "id": 2, "lead_id": 1,
     *     "full_name": "Nguyễn Thị C", "relationship": "Mẹ", "phone": "0912345678", "email": "c@example.com",
     *     "created_by": 1, "updated_by": null, "deleted_by": null,
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T08:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create($leadId, CreateLeadGuardianRequest $request, CreateLeadGuardianAction $action)
    {
        $data = $request->validated();
        $data['lead_id'] = (int) $leadId;

        $guardian = $action->handle($data);

        return $this->respondSuccess(new LeadGuardianResource($guardian), 'Thêm người giám hộ thành công.');
    }

    /**
     * Update guardian
     *
     * @urlParam leadId integer required The lead ID. Example: 1
     * @urlParam id integer required The guardian ID. Example: 2
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật người giám hộ thành công.",
     *   "data": {
     *     "id": 2, "lead_id": 1,
     *     "full_name": "Nguyễn Thị C", "relationship": "Mẹ", "phone": "0912345678", "email": "new@example.com",
     *     "created_by": 1, "updated_by": 1, "deleted_by": null,
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T09:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update($leadId, $id, UpdateLeadGuardianRequest $request, UpdateLeadGuardianAction $action)
    {
        $guardian = $action->handle($id, $request->validated());

        return $this->respondSuccess(new LeadGuardianResource($guardian), 'Cập nhật người giám hộ thành công.');
    }

    /**
     * Delete guardian
     *
     * @urlParam leadId integer required The lead ID. Example: 1
     * @urlParam id integer required The guardian ID. Example: 2
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa người giám hộ thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function delete($leadId, $id, DeleteLeadGuardianAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa người giám hộ thành công.');
    }
}
