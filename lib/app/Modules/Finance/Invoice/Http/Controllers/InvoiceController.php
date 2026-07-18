<?php

namespace App\Modules\Finance\Invoice\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Invoice\Actions\ApproveInvoiceAction;
use App\Modules\Finance\Invoice\Actions\CancelInvoiceAction;
use App\Modules\Finance\Invoice\Actions\CreateInvoiceAction;
use App\Modules\Finance\Invoice\Actions\DenyInvoiceAction;
use App\Modules\Finance\Invoice\Actions\DownloadInvoicePdfAction;
use App\Modules\Finance\Invoice\Actions\GetInvoiceAction;
use App\Modules\Finance\Invoice\Actions\ListInvoiceAction;
use App\Modules\Finance\Invoice\Actions\RecordPaymentAction;
use App\Modules\Finance\Invoice\Actions\RefundInvoiceAction;
use App\Modules\Finance\Invoice\Actions\UpdateInvoiceAction;
use App\Modules\Finance\Invoice\Http\Requests\CreateInvoiceRequest;
use App\Modules\Finance\Invoice\Http\Requests\InvoiceReasonRequest;
use App\Modules\Finance\Invoice\Http\Requests\RecordPaymentRequest;
use App\Modules\Finance\Invoice\Http\Requests\UpdateInvoiceRequest;
use App\Modules\Finance\Invoice\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;

/**
 * @group Finance - Invoice
 *
 * Manage receivable and payable invoices, their approval, payments and lifecycle
 * (invoice.md).
 *
 * @authenticated
 */
class InvoiceController extends Controller
{
    /**
     * List invoices
     *
     * @queryParam search string Search by code or note. Example: INV
     * @queryParam invoice_type string Filter: receivable|payable. Example: receivable
     * @queryParam status string Filter by status. Example: pending
     * @queryParam partner_type string Filter by partner type. Example: student
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam branch_id integer Filter by branch id. Example: 1
     * @queryParam invoice_date_from date Issued on/after (Y-m-d). Example: 2026-01-01
     * @queryParam invoice_date_to date Issued on/before (Y-m-d). Example: 2026-12-31
     * @queryParam due_date_from date Due on/after (Y-m-d). Example: 2026-01-01
     * @queryParam due_date_to date Due on/before (Y-m-d). Example: 2026-12-31
     * @queryParam overdue boolean Only overdue invoices with a balance. Example: 1
     * @queryParam sort_by string Sort column. Example: created_at
     * @queryParam sort_dir string asc|desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListInvoiceAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), InvoiceResource::class);
    }

    /**
     * Invoice detail
     *
     * Returns the invoice with items, payments and its status history.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"invoice": {"id": 1, "code": "INV-202606-00001"}, "histories": []}, "code": 200, "errors": null}
     */
    public function detail($id, GetInvoiceAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'invoice' => new InvoiceResource($result['invoice']),
            'histories' => $result['histories'],
        ]);
    }

    /**
     * Download invoice as PDF
     *
     * @urlParam id integer required The invoice ID. Example: 1
     */
    public function download($id, DownloadInvoicePdfAction $action)
    {
        return $action->handle($id)->download("invoice-{$id}.pdf");
    }

    /**
     * Create invoice
     *
     * @response 200 {"success": true, "msg": "Tạo hóa đơn thành công.", "data": {"id": 1, "code": "INV-202606-00001", "invoice_type": "receivable", "status": "pending"}, "code": 200, "errors": null}
     */
    public function create(CreateInvoiceRequest $request, CreateInvoiceAction $action)
    {
        $invoice = $action->handle($request->validated());

        return $this->respondSuccess(new InvoiceResource($invoice), 'Tạo hóa đơn thành công.');
    }

    /**
     * Update invoice
     *
     * Only draft/pending invoices can be edited. Code, business and type are immutable.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Cập nhật hóa đơn thành công.", "data": {"id": 1}, "code": 200, "errors": null}
     * @response 200 scenario="Not editable" {"success": false, "msg": "Chỉ có thể chỉnh sửa hóa đơn ở trạng thái nháp hoặc chưa thanh toán.", "data": null, "code": 200, "errors": null}
     */
    public function update(UpdateInvoiceRequest $request, $id, UpdateInvoiceAction $action)
    {
        try {
            $invoice = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new InvoiceResource($invoice), 'Cập nhật hóa đơn thành công.');
    }

    /**
     * Approve invoice
     *
     * Approves a payable invoice so it can be paid (invoice.md §IX).
     *
     * @urlParam id integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Duyệt hóa đơn thành công.", "data": {"id": 1, "status": "approved"}, "code": 200, "errors": null}
     */
    public function approve($id, ApproveInvoiceAction $action)
    {
        try {
            $invoice = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new InvoiceResource($invoice), 'Duyệt hóa đơn thành công.');
    }

    /**
     * Deny invoice
     *
     * Rejects a payable invoice pending approval.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Từ chối hóa đơn thành công.", "data": {"id": 1, "status": "cancelled"}, "code": 200, "errors": null}
     */
    public function deny(InvoiceReasonRequest $request, $id, DenyInvoiceAction $action)
    {
        try {
            $invoice = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new InvoiceResource($invoice), 'Từ chối hóa đơn thành công.');
    }

    /**
     * Cancel invoice
     *
     * @urlParam id integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Hủy hóa đơn thành công.", "data": {"id": 1, "status": "cancelled"}, "code": 200, "errors": null}
     */
    public function cancel(InvoiceReasonRequest $request, $id, CancelInvoiceAction $action)
    {
        try {
            $invoice = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new InvoiceResource($invoice), 'Hủy hóa đơn thành công.');
    }

    /**
     * Refund invoice
     *
     * Marks a paid invoice as refunded.
     *
     * @urlParam id integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Hoàn tiền hóa đơn thành công.", "data": {"id": 1, "status": "refunded"}, "code": 200, "errors": null}
     */
    public function refund(InvoiceReasonRequest $request, $id, RefundInvoiceAction $action)
    {
        try {
            $invoice = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new InvoiceResource($invoice), 'Hoàn tiền hóa đơn thành công.');
    }

    /**
     * Record payment
     *
     * Records a receipt (receivable, IN) or disbursement (payable, OUT) and updates
     * the invoice's paid/balance amounts and status (invoice.md §X).
     *
     * @urlParam id integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Ghi nhận thanh toán thành công.", "data": {"id": 1, "status": "paid", "balance_amount": "0.00"}, "code": 200, "errors": null}
     * @response 200 scenario="Not payable yet" {"success": false, "msg": "Hóa đơn chi phải được duyệt trước khi thanh toán.", "data": null, "code": 200, "errors": null}
     */
    public function pay(RecordPaymentRequest $request, $id, RecordPaymentAction $action)
    {
        try {
            $invoice = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new InvoiceResource($invoice), 'Ghi nhận thanh toán thành công.');
    }
}
