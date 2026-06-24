<?php

namespace App\Modules\Finance\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Wallet\Actions\GetWalletAction;
use App\Modules\Finance\Wallet\Actions\ListWalletAction;
use App\Modules\Finance\Wallet\Actions\ListWalletTransactionAction;
use App\Modules\Finance\Wallet\Actions\LockWalletAction;
use App\Modules\Finance\Wallet\Actions\WalletTransactionAction;
use App\Modules\Finance\Wallet\Http\Requests\AdjustWalletRequest;
use App\Modules\Finance\Wallet\Http\Requests\DepositRequest;
use App\Modules\Finance\Wallet\Http\Requests\PaymentRequest;
use App\Modules\Finance\Wallet\Http\Requests\RecordFromInvoiceRequest;
use App\Modules\Finance\Wallet\Http\Requests\RecordFromPaymentRequest;
use App\Modules\Finance\Wallet\Http\Requests\RefundRequest;
use App\Modules\Finance\Wallet\Http\Resources\WalletResource;
use App\Modules\Finance\Wallet\Http\Resources\WalletTransactionResource;
use Illuminate\Http\Request;

/**
 * @group Finance - Wallet
 *
 * Internal customer balances: list/inspect wallets, lock them, and post deposit /
 * payment / refund / adjustment ledger entries (wallet.md). Every balance change is an
 * immutable transaction with the before/after balance.
 *
 * @authenticated
 */
class WalletController extends Controller
{
    /**
     * List wallets
     *
     * @queryParam business_id integer Filter by business. Example: 1
     * @queryParam owner_type string Filter: parent|customer. Example: parent
     * @queryParam owner_id integer Filter by owner id. Example: 1
     * @queryParam status string Filter: active|locked|closed. Example: active
     * @queryParam balance_from number Minimum available balance. Example: 0
     * @queryParam balance_to number Maximum available balance. Example: 1000000
     * @queryParam sort_by string Sort column. Example: created_at
     * @queryParam sort_dir string asc|desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListWalletAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), WalletResource::class);
    }

    /**
     * Wallet detail
     *
     * Returns the wallet with its 20 most recent transactions.
     *
     * @urlParam id integer required The wallet ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"id": 1, "balance": "500000.00", "status": "active"}, "code": 200, "errors": null}
     */
    public function detail($id, GetWalletAction $action)
    {
        return $this->respondSuccess(new WalletResource($action->handle($id)));
    }

    /**
     * Transaction history
     *
     * @queryParam wallet_id integer Filter by wallet. Example: 1
     * @queryParam transaction_type string Filter: deposit|payment|refund|bonus|adjustment|expire. Example: deposit
     * @queryParam reference_type string Filter: invoice|payment|refund|debt|enrollment|transaction. Example: invoice
     * @queryParam reference_id integer Filter by referenced document id. Example: 1
     * @queryParam date_from date On/after (Y-m-d). Example: 2026-06-01
     * @queryParam date_to date On/before (Y-m-d). Example: 2026-06-30
     * @queryParam sort_by string Sort column. Example: created_at
     * @queryParam sort_dir string asc|desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function transactions(Request $request, ListWalletTransactionAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), WalletTransactionResource::class);
    }

    /**
     * Lock wallet
     *
     * A locked wallet blocks deposit / payment / refund (BR012).
     *
     * @urlParam id integer required The wallet ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Khóa ví thành công.", "data": {"id": 1, "status": "locked"}, "code": 200, "errors": null}
     */
    public function lock($id, LockWalletAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('lock', $id),
            'Khóa ví thành công.',
            fn ($wallet) => new WalletResource($wallet),
        );
    }

    /**
     * Unlock wallet
     *
     * @urlParam id integer required The wallet ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Mở khóa ví thành công.", "data": {"id": 1, "status": "active"}, "code": 200, "errors": null}
     */
    public function unlock($id, LockWalletAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('unlock', $id),
            'Mở khóa ví thành công.',
            fn ($wallet) => new WalletResource($wallet),
        );
    }

    /**
     * Deposit
     *
     * Credits the wallet (BR003 amount > 0, BR004 transaction, BR005 balance updated).
     *
     * @response 200 {"success": true, "msg": "Nạp tiền thành công.", "data": {"id": 1, "transaction_type": "deposit", "balance_after": "500000.00"}, "code": 200, "errors": null}
     * @response 200 scenario="Locked" {"success": false, "msg": "Ví đang bị khóa.", "data": null, "code": 200, "errors": null}
     */
    public function deposit(DepositRequest $request, WalletTransactionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('deposit', $request->validated()),
            'Nạp tiền thành công.',
            fn ($transaction) => new WalletTransactionResource($transaction),
        );
    }

    /**
     * Payment
     *
     * Debits the wallet; the balance cannot go negative (BR006).
     *
     * @response 200 {"success": true, "msg": "Thanh toán thành công.", "data": {"id": 1, "transaction_type": "payment"}, "code": 200, "errors": null}
     * @response 200 scenario="Insufficient" {"success": false, "msg": "Số dư ví không đủ.", "data": null, "code": 200, "errors": null}
     */
    public function payment(PaymentRequest $request, WalletTransactionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('payment', $request->validated()),
            'Thanh toán thành công.',
            fn ($transaction) => new WalletTransactionResource($transaction),
        );
    }

    /**
     * Refund
     *
     * Credits back against an original payment transaction (BR008); cannot exceed the
     * amount paid (BR009).
     *
     * @response 200 {"success": true, "msg": "Hoàn tiền thành công.", "data": {"id": 1, "transaction_type": "refund"}, "code": 200, "errors": null}
     */
    public function refund(RefundRequest $request, WalletTransactionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('refund', $request->validated()),
            'Hoàn tiền thành công.',
            fn ($transaction) => new WalletTransactionResource($transaction),
        );
    }

    /**
     * Adjustment
     *
     * Increases or decreases the balance with a mandatory reason (BR010), recorded in the
     * ledger (BR011).
     *
     * @response 200 {"success": true, "msg": "Điều chỉnh ví thành công.", "data": {"id": 1, "transaction_type": "adjustment"}, "code": 200, "errors": null}
     */
    public function adjustment(AdjustWalletRequest $request, WalletTransactionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('adjust', $request->validated()),
            'Điều chỉnh ví thành công.',
            fn ($transaction) => new WalletTransactionResource($transaction),
        );
    }

    /**
     * Record balance from invoice
     *
     * Debits the wallet for an invoice and links the transaction to it.
     *
     * @response 200 {"success": true, "msg": "Ghi nhận số dư từ hóa đơn thành công.", "data": {"id": 1, "transaction_type": "payment", "reference_type": "invoice", "reference_id": 1}, "code": 200, "errors": null}
     */
    public function fromInvoice(RecordFromInvoiceRequest $request, WalletTransactionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('recordFromInvoice', $request->validated()),
            'Ghi nhận số dư từ hóa đơn thành công.',
            fn ($transaction) => new WalletTransactionResource($transaction),
        );
    }

    /**
     * Record balance from payment
     *
     * Credits the wallet from a payment order and links the transaction to it.
     *
     * @response 200 {"success": true, "msg": "Ghi nhận số dư từ đơn thanh toán thành công.", "data": {"id": 1, "transaction_type": "deposit", "reference_type": "payment", "reference_id": 1}, "code": 200, "errors": null}
     */
    public function fromPayment(RecordFromPaymentRequest $request, WalletTransactionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('recordFromPayment', $request->validated()),
            'Ghi nhận số dư từ đơn thanh toán thành công.',
            fn ($transaction) => new WalletTransactionResource($transaction),
        );
    }
}
