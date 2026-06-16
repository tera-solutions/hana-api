<?php

namespace App\Modules\Finance\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Payment\Actions\CancelPaymentAction;
use App\Modules\Finance\Payment\Actions\ConfirmPaymentAction;
use App\Modules\Finance\Payment\Actions\CreatePaymentAction;
use App\Modules\Finance\Payment\Actions\GetPaymentAction;
use App\Modules\Finance\Payment\Actions\ListPaymentAction;
use App\Modules\Finance\Payment\Actions\ReceiptPaymentAction;
use App\Modules\Finance\Payment\Actions\RefundPaymentAction;
use App\Modules\Finance\Payment\Actions\ReversePaymentAction;
use App\Modules\Finance\Payment\Actions\UpdatePaymentAction;
use App\Modules\Finance\Payment\Http\Requests\CreatePaymentRequest;
use App\Modules\Finance\Payment\Http\Requests\PaymentReasonRequest;
use App\Modules\Finance\Payment\Http\Requests\RefundPaymentRequest;
use App\Modules\Finance\Payment\Http\Requests\UpdatePaymentRequest;
use App\Modules\Finance\Payment\Http\Resources\PaymentResource;
use Illuminate\Http\Request;

/**
 * @group Finance - Payment
 *
 * Record and manage real cash-flow transactions — receipts (IN) and disbursements
 * (OUT) — with confirm/cancel/reverse/refund and fund-balance effects (payment.md).
 *
 * @authenticated
 */
class PaymentController extends Controller
{
    /**
     * List payments
     *
     * @queryParam search string Search by payment no, reference or description. Example: PAY
     * @queryParam payment_direction string Filter: in|out. Example: in
     * @queryParam payment_type string Filter by type. Example: tuition_payment
     * @queryParam status string Filter: draft|pending|confirmed|cancelled|reversed|refunded. Example: confirmed
     * @queryParam account_id integer Filter by fund id. Example: 1
     * @queryParam partner_type string Filter by partner type. Example: student
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam branch_id integer Filter by branch id. Example: 1
     * @queryParam invoice_id integer Filter by linked invoice id. Example: 1
     * @queryParam date_from date Payment date on/after (Y-m-d). Example: 2026-01-01
     * @queryParam date_to date Payment date on/before (Y-m-d). Example: 2026-12-31
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListPaymentAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), PaymentResource::class);
    }

    /**
     * Payment detail
     *
     * Returns the payment with allocations and its status history.
     *
     * @urlParam id integer required The payment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"payment": {"id": 1, "payment_no": "PAY2026/000001"}, "histories": []}, "code": 200, "errors": null}
     */
    public function detail($id, GetPaymentAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'payment' => new PaymentResource($result['payment']),
            'histories' => $result['histories'],
        ]);
    }

    /**
     * Create payment
     *
     * Creates a transaction in `draft` (or `pending`). It does not move any balance
     * until confirmed (payment.md §VIII, BR-03).
     *
     * @response 200 {"success": true, "msg": "Tạo giao dịch thành công.", "data": {"id": 1, "payment_no": "PAY2026/000001", "status": "draft"}, "code": 200, "errors": null}
     */
    public function create(CreatePaymentRequest $request, CreatePaymentAction $action)
    {
        return $this->respondSuccess(new PaymentResource($action->handle($request->validated())), 'Tạo giao dịch thành công.');
    }

    /**
     * Update payment
     *
     * Only draft/pending payments can be edited (BR-04).
     *
     * @urlParam id integer required The payment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Cập nhật giao dịch thành công.", "data": {"id": 1}, "code": 200, "errors": null}
     * @response 200 scenario="Not editable" {"success": false, "msg": "Chỉ có thể chỉnh sửa giao dịch ở trạng thái nháp hoặc chờ xác nhận.", "data": null, "code": 200, "errors": null}
     */
    public function update(UpdatePaymentRequest $request, $id, UpdatePaymentAction $action)
    {
        try {
            $payment = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PaymentResource($payment), 'Cập nhật giao dịch thành công.');
    }

    /**
     * Confirm payment
     *
     * Moves the fund balance and applies allocations to invoices (payment.md §VIII).
     *
     * @urlParam id integer required The payment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Xác nhận giao dịch thành công.", "data": {"id": 1, "status": "confirmed"}, "code": 200, "errors": null}
     */
    public function confirm($id, ConfirmPaymentAction $action)
    {
        try {
            $payment = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PaymentResource($payment), 'Xác nhận giao dịch thành công.');
    }

    /**
     * Cancel payment
     *
     * Allowed only before confirmation (payment.md §XI).
     *
     * @urlParam id integer required The payment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Hủy giao dịch thành công.", "data": {"id": 1, "status": "cancelled"}, "code": 200, "errors": null}
     */
    public function cancel(PaymentReasonRequest $request, $id, CancelPaymentAction $action)
    {
        try {
            $payment = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PaymentResource($payment), 'Hủy giao dịch thành công.');
    }

    /**
     * Reverse payment
     *
     * Books an equal, opposite transaction undoing a confirmed payment (BR-06).
     *
     * @urlParam id integer required The payment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Đảo giao dịch thành công.", "data": {"id": 1, "status": "reversed"}, "code": 200, "errors": null}
     */
    public function reverse(PaymentReasonRequest $request, $id, ReversePaymentAction $action)
    {
        try {
            $payment = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PaymentResource($payment), 'Đảo giao dịch thành công.');
    }

    /**
     * Refund payment
     *
     * Books a refund transaction for part or all of a confirmed payment (payment.md §X).
     *
     * @urlParam id integer required The payment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Hoàn tiền thành công.", "data": {"id": 1, "status": "refunded"}, "code": 200, "errors": null}
     */
    public function refund(RefundPaymentRequest $request, $id, RefundPaymentAction $action)
    {
        try {
            $payment = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PaymentResource($payment), 'Hoàn tiền thành công.');
    }

    /**
     * Payment receipt
     *
     * Returns the payment data used to render a receipt/voucher (payment.md §II).
     *
     * @urlParam id integer required The payment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"id": 1, "payment_no": "PAY2026/000001"}, "code": 200, "errors": null}
     */
    public function receipt($id, ReceiptPaymentAction $action)
    {
        return $this->respondSuccess(new PaymentResource($action->handle($id)));
    }
}
