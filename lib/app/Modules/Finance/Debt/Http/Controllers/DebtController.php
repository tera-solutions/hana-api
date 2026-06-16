<?php

namespace App\Modules\Finance\Debt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Debt\Actions\AdjustDebtAction;
use App\Modules\Finance\Debt\Actions\AgingDebtAction;
use App\Modules\Finance\Debt\Actions\ApproveWriteoffAction;
use App\Modules\Finance\Debt\Actions\CollectDebtAction;
use App\Modules\Finance\Debt\Actions\DashboardDebtAction;
use App\Modules\Finance\Debt\Actions\DenyWriteoffAction;
use App\Modules\Finance\Debt\Actions\GetDebtAction;
use App\Modules\Finance\Debt\Actions\ListDebtAction;
use App\Modules\Finance\Debt\Actions\ReconcileDebtAction;
use App\Modules\Finance\Debt\Actions\WriteoffDebtAction;
use App\Modules\Finance\Debt\Http\Requests\AdjustDebtRequest;
use App\Modules\Finance\Debt\Http\Requests\CollectDebtRequest;
use App\Modules\Finance\Debt\Http\Requests\DenyWriteoffRequest;
use App\Modules\Finance\Debt\Http\Requests\WriteoffDebtRequest;
use App\Modules\Finance\Debt\Http\Resources\DebtAdjustmentResource;
use App\Modules\Finance\Debt\Http\Resources\DebtResource;
use Illuminate\Http\Request;

/**
 * @group Finance - Debt
 *
 * Receivable/payable debt computed live from invoices and payments, with aging,
 * dashboard, adjustments, write-off approval, collection and reconciliation (debt.md).
 *
 * @authenticated
 */
class DebtController extends Controller
{
    /**
     * List debts
     *
     * @queryParam search string Search by invoice code. Example: INV
     * @queryParam invoice_type string Filter: receivable|payable. Example: receivable
     * @queryParam partner_type string Filter by partner type. Example: student
     * @queryParam status string Filter: current|overdue|written_off|closed. Example: overdue
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam branch_id integer Filter by branch id. Example: 1
     * @queryParam min_amount number Minimum outstanding. Example: 1000000
     * @queryParam max_amount number Maximum outstanding. Example: 5000000
     * @queryParam overdue boolean Only overdue debts. Example: 1
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListDebtAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), DebtResource::class);
    }

    /**
     * Aging report
     *
     * Outstanding grouped into current / 1-30 / 31-60 / 61-90 / >90 day buckets.
     *
     * @queryParam invoice_type string Filter: receivable|payable. Example: receivable
     * @queryParam business_id integer Filter by business id. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"current": 0, "overdue_1_30": 0, "overdue_31_60": 0, "overdue_61_90": 0, "overdue_90_plus": 0, "total": 0}, "code": 200, "errors": null}
     */
    public function aging(Request $request, AgingDebtAction $action)
    {
        return $this->respondSuccess($action->handle($request->all()));
    }

    /**
     * Debt dashboard
     *
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam branch_id integer Filter by branch id. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"total_receivable": 0, "total_payable": 0, "overdue_amount": 0, "top_debtors": [], "top_creditors": []}, "code": 200, "errors": null}
     */
    public function dashboard(Request $request, DashboardDebtAction $action)
    {
        return $this->respondSuccess($action->handle($request->all()));
    }

    /**
     * Debt detail
     *
     * Returns the invoice with its payment history and debt adjustments (debt.md §VI).
     *
     * @urlParam invoiceId integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"debt": {"invoice_id": 1, "outstanding": "1000000.00"}, "payments": [], "adjustments": []}, "code": 200, "errors": null}
     */
    public function detail($invoiceId, GetDebtAction $action)
    {
        $result = $action->handle($invoiceId);

        return $this->respondSuccess([
            'debt' => new DebtResource($result['invoice']),
            'outstanding' => $result['outstanding'],
            'overdue_days' => $result['overdue_days'],
            'debt_status' => $result['debt_status'],
            'payments' => $result['payments'],
            'adjustments' => DebtAdjustmentResource::collection($result['adjustments']),
        ]);
    }

    /**
     * Adjust debt
     *
     * Records a correction or late discount, reducing the invoice (debt.md §XI).
     *
     * @urlParam invoiceId integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Điều chỉnh công nợ thành công.", "data": {"adjustment": {"id": 1, "adjustment_type": "discount"}, "debt": {"invoice_id": 1}}, "code": 200, "errors": null}
     */
    public function adjust(AdjustDebtRequest $request, $invoiceId, AdjustDebtAction $action)
    {
        try {
            $result = $action->handle($invoiceId, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess([
            'adjustment' => new DebtAdjustmentResource($result['adjustment']),
            'debt' => new DebtResource($result['invoice']),
        ], 'Điều chỉnh công nợ thành công.');
    }

    /**
     * Request write-off
     *
     * Raises a pending write-off; it applies only after approval (debt.md §XII, BR-08).
     *
     * @urlParam invoiceId integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Đã gửi yêu cầu xóa nợ.", "data": {"id": 1, "status": "pending"}, "code": 200, "errors": null}
     */
    public function writeoff(WriteoffDebtRequest $request, $invoiceId, WriteoffDebtAction $action)
    {
        try {
            $adjustment = $action->handle($invoiceId, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new DebtAdjustmentResource($adjustment), 'Đã gửi yêu cầu xóa nợ.');
    }

    /**
     * Approve write-off
     *
     * @urlParam id integer required The write-off adjustment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Duyệt xóa nợ thành công.", "data": {"id": 1, "status": "approved"}, "code": 200, "errors": null}
     */
    public function approveWriteoff($id, ApproveWriteoffAction $action)
    {
        try {
            $adjustment = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new DebtAdjustmentResource($adjustment), 'Duyệt xóa nợ thành công.');
    }

    /**
     * Deny write-off
     *
     * @urlParam id integer required The write-off adjustment ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Từ chối xóa nợ thành công.", "data": {"id": 1, "status": "rejected"}, "code": 200, "errors": null}
     */
    public function denyWriteoff(DenyWriteoffRequest $request, $id, DenyWriteoffAction $action)
    {
        try {
            $adjustment = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new DebtAdjustmentResource($adjustment), 'Từ chối xóa nợ thành công.');
    }

    /**
     * Collect debt
     *
     * Records a payment against the invoice to reduce the outstanding (debt.md §X).
     *
     * @urlParam invoiceId integer required The invoice ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thu hồi công nợ thành công.", "data": {"debt": {"invoice_id": 1, "outstanding": "0.00"}}, "code": 200, "errors": null}
     */
    public function collect(CollectDebtRequest $request, $invoiceId, CollectDebtAction $action)
    {
        try {
            $result = $action->handle($invoiceId, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess([
            'debt' => new DebtResource($result['invoice']),
            'outstanding' => $result['outstanding'],
            'payments' => $result['payments'],
        ], 'Thu hồi công nợ thành công.');
    }

    /**
     * Reconcile debts
     *
     * Compares each invoice's paid amount against its confirmed payment allocations
     * and reports mismatches (debt.md §XIII).
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"matched_count": 0, "mismatch_count": 0, "mismatches": []}, "code": 200, "errors": null}
     */
    public function reconcile(Request $request, ReconcileDebtAction $action)
    {
        return $this->respondSuccess($action->handle($request->all()));
    }
}
