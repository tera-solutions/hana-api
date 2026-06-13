<?php

namespace App\Modules\CRM\Lead\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Lead\Actions\CreateLeadAction;
use App\Modules\CRM\Lead\Actions\GetLeadAction;
use App\Modules\CRM\Lead\Actions\ListLeadAction;
use App\Modules\CRM\Lead\Actions\RestoreLeadAction;
use App\Modules\CRM\Lead\Actions\SuspendLeadAction;
use App\Modules\CRM\Lead\Actions\UpdateLeadAction;
use App\Modules\CRM\Lead\Http\Requests\CreateLeadRequest;
use App\Modules\CRM\Lead\Http\Requests\SuspendLeadRequest;
use App\Modules\CRM\Lead\Http\Requests\UpdateLeadRequest;
use App\Modules\CRM\Lead\Http\Resources\LeadResource;
use Illuminate\Http\Request;

/**
 * @group CRM - Lead
 *
 * Manage leads / prospective customers (lead.md §2–§7).
 *
 * @authenticated
 */
class LeadController extends Controller
{
    /**
     * List leads
     *
     * @queryParam search string Search by code, name, email or phone. Example: Nguyễn
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam branch_id integer Filter by branch id. Example: 1
     * @queryParam status string Filter by status: pending, verified, studying, inactive. Example: pending
     * @queryParam source string Filter by lead source. Example: facebook
     * @queryParam owner_id integer Filter by assigned staff id. Example: 1
     * @queryParam name string Partial match on name. Example: Văn A
     * @queryParam email string Partial match on email. Example: example.com
     * @queryParam phone string Partial match on phone. Example: 0901
     * @queryParam contacted_from date Filter leads contacted on or after (Y-m-d). Example: 2026-01-01
     * @queryParam contacted_to date Filter leads contacted on or before (Y-m-d). Example: 2026-12-31
     * @queryParam tag_ids integer[] Filter by tag ids. Example: [1,2]
     * @queryParam course_ids integer[] Filter by interested course ids. Example: [1]
     * @queryParam sort_by string Sort column: code, name, status, created_at. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {
     *         "id": 1, "code": "LEAD000001", "name": "Nguyễn Văn A", "gender": "male",
     *         "dob": "2010-03-20", "email": "a@example.com", "phone": "0901234567",
     *         "source": "facebook", "status": "pending", "note": null,
     *         "previous_status": null, "suspended_at": null, "suspend_reason": null, "suspended_by": null,
     *         "guardians_count": 1, "students_count": 0,
     *         "business_id": 1, "branch_id": 1, "owner_id": 1,
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
    public function list(Request $request, ListLeadAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), LeadResource::class);
    }

    /**
     * Lead detail
     *
     * Returns the full lead record with all relations and its change history.
     *
     * @urlParam id integer required The lead ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "lead": {
     *       "id": 1, "code": "LEAD000001", "name": "Nguyễn Văn A", "gender": "male",
     *       "dob": "2010-03-20", "email": "a@example.com", "phone": "0901234567",
     *       "source": "facebook", "status": "pending", "note": null,
     *       "previous_status": null, "suspended_at": null, "suspend_reason": null, "suspended_by": null,
     *       "guardians_count": 1, "students_count": 1,
     *       "business_id": 1, "business": {"id": 1, "name": "Hana English"},
     *       "branch_id": 1, "branch": {"id": 1, "name": "Hà Nội"},
     *       "owner_id": 1, "owner": {"id": 1, "name": "Trần Thị B", "avatar": null},
     *       "guardians": [{"id": 1, "full_name": "Nguyễn Văn B", "relationship": "Bố", "phone": "0907654321", "email": null}],
     *       "students": [{"id": 1, "code": "STU000001", "name": "Nguyễn Thị C", "relationship": "father"}],
     *       "tags": [{"id": 1, "name": "Tiềm năng", "color": "#FF5733"}],
     *       "courses": [{"id": 1, "code": "C001", "name": "English Basics"}],
     *       "created_by": 1, "updated_by": null, "deleted_by": null,
     *       "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-01T08:00:00.000000Z", "deleted_at": null
     *     },
     *     "histories": [
     *       {"id": 1, "lead_id": 1, "action": "created", "from_status": null, "to_status": "pending", "reason": null, "note": null, "created_by": 1, "created_at": "2026-06-01T08:00:00.000000Z"}
     *     ]
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetLeadAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'lead' => new LeadResource($result['lead']),
            'histories' => $result['histories'],
        ]);
    }

    /**
     * Create lead
     *
     * Creates a new lead with optional guardians, linked students, tags and courses.
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo khách hàng thành công.",
     *   "data": {
     *     "id": 1, "code": "LEAD000001", "name": "Nguyễn Văn A", "gender": "male",
     *     "dob": "2010-03-20", "email": "a@example.com", "phone": "0901234567",
     *     "source": "facebook", "status": "pending", "note": null,
     *     "business_id": 1, "branch_id": 1, "owner_id": 1,
     *     "guardians": [{"id": 1, "full_name": "Nguyễn Văn B", "relationship": "Bố", "phone": "0907654321", "email": null}],
     *     "students": [], "tags": [], "courses": [],
     *     "created_by": 1, "updated_by": null, "deleted_by": null,
     *     "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-01T08:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateLeadRequest $request, CreateLeadAction $action)
    {
        $lead = $action->handle($request->validated());

        return $this->respondSuccess(new LeadResource($lead), 'Tạo khách hàng thành công.');
    }

    /**
     * Update lead
     *
     * Partial update — only send fields to change. Code, business and status are immutable.
     *
     * @urlParam id integer required The lead ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật khách hàng thành công.",
     *   "data": {
     *     "id": 1, "code": "LEAD000001", "name": "Nguyễn Văn A", "gender": "male",
     *     "dob": "2010-03-20", "email": "a@example.com", "phone": "0901234567",
     *     "source": "zalo", "status": "pending", "note": "Đã tư vấn",
     *     "business_id": 1, "branch_id": 1, "owner_id": 2,
     *     "guardians": [], "students": [], "tags": [], "courses": [],
     *     "created_by": 1, "updated_by": 2, "deleted_by": null,
     *     "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-13T09:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateLeadRequest $request, $id, UpdateLeadAction $action)
    {
        $lead = $action->handle($id, $request->validated());

        return $this->respondSuccess(new LeadResource($lead), 'Cập nhật khách hàng thành công.');
    }

    /**
     * Suspend lead
     *
     * Moves a lead to "inactive" status and records the reason in the history.
     *
     * @urlParam id integer required The lead ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Ngừng khách hàng thành công.",
     *   "data": {
     *     "id": 1, "code": "LEAD000001", "name": "Nguyễn Văn A",
     *     "status": "inactive", "previous_status": "pending",
     *     "suspended_at": "2026-06-13T09:00:00.000000Z",
     *     "suspend_reason": "Không còn nhu cầu", "suspended_by": 1,
     *     "business_id": 1, "branch_id": 1, "owner_id": 1,
     *     "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-13T09:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Already inactive" {
     *   "success": false,
     *   "msg": "Khách hàng đang ở trạng thái ngừng.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function suspend(SuspendLeadRequest $request, $id, SuspendLeadAction $action)
    {
        try {
            $lead = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LeadResource($lead), 'Ngừng khách hàng thành công.');
    }

    /**
     * Restore lead
     *
     * Reactivates an inactive lead, returning it to its pre-suspend status (or "pending"
     * when unknown).
     *
     * @urlParam id integer required The lead ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Khôi phục khách hàng thành công.",
     *   "data": {
     *     "id": 1, "code": "LEAD000001", "name": "Nguyễn Văn A",
     *     "status": "pending", "previous_status": null,
     *     "suspended_at": null, "suspend_reason": null, "suspended_by": null,
     *     "business_id": 1, "branch_id": 1, "owner_id": 1,
     *     "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-13T10:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Not inactive" {
     *   "success": false,
     *   "msg": "Chỉ có thể khôi phục khách hàng đang ngừng.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function restore($id, RestoreLeadAction $action)
    {
        try {
            $lead = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LeadResource($lead), 'Khôi phục khách hàng thành công.');
    }
}
