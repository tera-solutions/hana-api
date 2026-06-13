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
 */
class LeadController extends Controller
{
    public function list(Request $request, ListLeadAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), LeadResource::class);
    }

    public function detail($id, GetLeadAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'lead' => new LeadResource($result['lead']),
            'histories' => $result['histories'],
        ]);
    }

    public function create(CreateLeadRequest $request, CreateLeadAction $action)
    {
        $lead = $action->handle($request->validated());

        return $this->respondSuccess(new LeadResource($lead), 'Tạo khách hàng thành công.');
    }

    public function update(UpdateLeadRequest $request, $id, UpdateLeadAction $action)
    {
        $lead = $action->handle($id, $request->validated());

        return $this->respondSuccess(new LeadResource($lead), 'Cập nhật khách hàng thành công.');
    }

    public function suspend(SuspendLeadRequest $request, $id, SuspendLeadAction $action)
    {
        try {
            $lead = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LeadResource($lead), 'Ngừng khách hàng thành công.');
    }

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
