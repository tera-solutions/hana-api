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
 */
class LeadStudentController extends Controller
{
    public function list($leadId, Request $request, ListLeadStudentAction $action)
    {
        return $this->respondPaginated($action->handle($leadId, $request->all()), LeadStudentResource::class);
    }

    public function create($leadId, CreateLeadStudentRequest $request, CreateLeadStudentAction $action)
    {
        $data = $request->validated();
        $data['lead_id'] = (int) $leadId;

        $link = $action->handle($data);

        return $this->respondSuccess(new LeadStudentResource($link), 'Liên kết học viên thành công.');
    }

    public function update($leadId, $id, UpdateLeadStudentRequest $request, UpdateLeadStudentAction $action)
    {
        $link = $action->handle($id, $request->validated());

        return $this->respondSuccess(new LeadStudentResource($link), 'Cập nhật liên kết học viên thành công.');
    }

    public function delete($leadId, $id, DeleteLeadStudentAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Gỡ liên kết học viên thành công.');
    }
}
