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
 */
class LeadGuardianController extends Controller
{
    public function list($leadId, Request $request, ListLeadGuardianAction $action)
    {
        return $this->respondPaginated($action->handle($leadId, $request->all()), LeadGuardianResource::class);
    }

    public function create($leadId, CreateLeadGuardianRequest $request, CreateLeadGuardianAction $action)
    {
        $data = $request->validated();
        $data['lead_id'] = (int) $leadId;

        $guardian = $action->handle($data);

        return $this->respondSuccess(new LeadGuardianResource($guardian), 'Thêm người giám hộ thành công.');
    }

    public function update($leadId, $id, UpdateLeadGuardianRequest $request, UpdateLeadGuardianAction $action)
    {
        $guardian = $action->handle($id, $request->validated());

        return $this->respondSuccess(new LeadGuardianResource($guardian), 'Cập nhật người giám hộ thành công.');
    }

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
