<?php

namespace App\Modules\CRM\Lead\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Lead\Actions\CreateLeadStudentAction;
use App\Modules\CRM\Lead\Actions\DeleteLeadStudentAction;
use App\Modules\CRM\Lead\Actions\ListLeadStudentAction;
use App\Modules\CRM\Lead\Actions\UpdateLeadStudentAction;
use App\Modules\CRM\Lead\Http\Requests\CreateLeadStudentRequest;
use App\Modules\CRM\Lead\Http\Requests\UpdateLeadStudentRequest;
use App\Modules\CRM\Lead\Http\Resources\LeadStudentResource;
use Illuminate\Http\Request;

/**
 * @group CRM - Lead Students
 *
 * Manage the students linked to a lead (lead.md §9 "Liên kết học viên").
 *
 * @authenticated
 */
class LeadStudentController extends Controller
{
    /**
     * List students linked to a lead
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
     *         "student_id": 5,
     *         "student": {"id": 5, "code": "STU000005", "name": "Nguyễn Thị C", "level": "beginner", "status": "active"},
     *         "relationship": "father",
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
    public function list($leadId, Request $request, ListLeadStudentAction $action)
    {
        return $this->respondPaginated($action->handle($leadId, $request->all()), LeadStudentResource::class);
    }

    /**
     * Link a student to a lead
     *
     * @urlParam leadId integer required The lead ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Liên kết học viên thành công.",
     *   "data": {
     *     "id": 1, "lead_id": 1,
     *     "student_id": 5,
     *     "relationship": "father",
     *     "created_by": 1, "updated_by": null, "deleted_by": null,
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T08:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create($leadId, CreateLeadStudentRequest $request, CreateLeadStudentAction $action)
    {
        $data = $request->validated();
        $data['lead_id'] = (int) $leadId;

        $link = $action->handle($data);

        return $this->respondSuccess(new LeadStudentResource($link), 'Liên kết học viên thành công.');
    }

    /**
     * Update student–lead relationship
     *
     * Only the relationship type is editable; the lead and student are immutable.
     *
     * @urlParam leadId integer required The lead ID. Example: 1
     * @urlParam id integer required The link record ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật liên kết học viên thành công.",
     *   "data": {
     *     "id": 1, "lead_id": 1,
     *     "student_id": 5,
     *     "relationship": "mother",
     *     "created_by": 1, "updated_by": 1, "deleted_by": null,
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T09:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update($leadId, $id, UpdateLeadStudentRequest $request, UpdateLeadStudentAction $action)
    {
        $link = $action->handle($id, $request->validated());

        return $this->respondSuccess(new LeadStudentResource($link), 'Cập nhật liên kết học viên thành công.');
    }

    /**
     * Unlink a student from a lead
     *
     * @urlParam leadId integer required The lead ID. Example: 1
     * @urlParam id integer required The link record ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Gỡ liên kết học viên thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function delete($leadId, $id, DeleteLeadStudentAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Gỡ liên kết học viên thành công.');
    }
}
