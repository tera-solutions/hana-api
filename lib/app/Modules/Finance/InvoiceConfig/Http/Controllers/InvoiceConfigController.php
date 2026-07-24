<?php

namespace App\Modules\Finance\InvoiceConfig\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Invoice\Services\InvoiceService;
use App\Modules\Finance\InvoiceConfig\Http\Requests\UpdateInvoiceConfigRequest;
use App\Modules\Finance\InvoiceConfig\Http\Resources\InvoiceConfigResource;
use App\Modules\Finance\InvoiceConfig\Services\InvoiceConfigService;
use Illuminate\Http\Request;
use Package\Tenancy\TenantContext;

/**
 * @group Finance - Invoice Config
 *
 * Per-business recurring (monthly) invoice generation settings.
 *
 * @authenticated
 */
class InvoiceConfigController extends Controller
{
    public function __construct(private InvoiceConfigService $service) {}

    /**
     * Get invoice config
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"id": 1, "business_id": 1, "auto_generate": true, "billing_day": 1, "due_days": 7}, "code": 200, "errors": null}
     */
    public function show(Request $request)
    {
        $businessId = TenantContext::businessId() ?? $request->integer('business_id');

        return $this->respondSuccess(new InvoiceConfigResource($this->service->get($businessId)));
    }

    /**
     * Update invoice config
     */
    public function update(UpdateInvoiceConfigRequest $request)
    {
        $businessId = TenantContext::businessId() ?? $request->integer('business_id');

        return $this->respondSuccess(
            new InvoiceConfigResource($this->service->update($businessId, $request->validated())),
            'Cập nhật cấu hình hóa đơn thành công.',
        );
    }

    /**
     * Generate now
     *
     * Force-runs recurring tuition billing for the caller's business today,
     * regardless of the configured billing day.
     *
     * @response 200 {"success": true, "msg": "Đã tạo hóa đơn.", "data": {"invoices_created": 48, "period": "07/2026"}, "code": 200, "errors": null}
     */
    public function generateNow(Request $request, InvoiceService $invoices)
    {
        $businessId = TenantContext::businessId() ?? $request->integer('business_id');

        return $this->respondSuccess($this->service->generateNow($invoices, $businessId), 'Đã tạo hóa đơn.');
    }
}
