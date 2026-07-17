<?php

namespace App\Modules\Finance\WalletRequest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\WalletRequest\Actions\ApproveWalletRequestAction;
use App\Modules\Finance\WalletRequest\Actions\CancelWalletRequestAction;
use App\Modules\Finance\WalletRequest\Actions\CompleteWalletRequestAction;
use App\Modules\Finance\WalletRequest\Actions\CreateWalletRequestAction;
use App\Modules\Finance\WalletRequest\Actions\GetWalletRequestAction;
use App\Modules\Finance\WalletRequest\Actions\ListWalletRequestAction;
use App\Modules\Finance\WalletRequest\Actions\RejectWalletRequestAction;
use App\Modules\Finance\WalletRequest\Http\Requests\CompleteWalletRequestRequest;
use App\Modules\Finance\WalletRequest\Http\Requests\CreateWalletRequestRequest;
use App\Modules\Finance\WalletRequest\Http\Requests\RejectWalletRequestRequest;
use App\Modules\Finance\WalletRequest\Http\Resources\WalletRequestResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Finance - Wallet Request
 *
 * Deposit/withdraw requests against a teacher's own wallet — no payment gateway,
 * an admin reviews and settles the money outside the system, then marks the
 * request complete, which is the only step that touches the wallet ledger.
 *
 * @authenticated
 */
class WalletRequestController extends Controller
{
    /**
     * List wallet requests
     *
     * @queryParam wallet_id integer Filter by wallet. Example: 1
     * @queryParam request_type string Filter: deposit|withdraw. Example: withdraw
     * @queryParam status string Filter: pending|approved|rejected|completed|cancelled. Example: pending
     * @queryParam search string Search by request code. Example: WR
     * @queryParam date_from date Created on/after (Y-m-d). Example: 2026-07-01
     * @queryParam date_to date Created on/before (Y-m-d). Example: 2026-07-31
     * @queryParam sort_by string Sort column. Example: created_at
     * @queryParam sort_dir string asc|desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function list(Request $request, ListWalletRequestAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), WalletRequestResource::class);
    }

    /**
     * Wallet request detail
     *
     * @urlParam id integer required The request ID. Example: 1
     */
    public function detail($id, GetWalletRequestAction $action)
    {
        return $this->respondSuccess(new WalletRequestResource($action->handle($id)));
    }

    /**
     * Create a deposit/withdraw request
     *
     * Always against the acting teacher's OWN wallet — `business_id`/`user_id`
     * come from the token, not the request body.
     *
     * @response 200 {"success": true, "msg": "Tạo yêu cầu thành công.", "data": {"id": 1, "status": "pending"}, "code": 200, "errors": null}
     */
    public function create(CreateWalletRequestRequest $request, CreateWalletRequestAction $action)
    {
        try {
            $walletRequest = $action->handle(array_merge($request->validated(), [
                'business_id' => Auth::guard('api')->user()?->business_id,
                'user_id' => Auth::guard('api')->id(),
            ]));
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new WalletRequestResource($walletRequest), 'Tạo yêu cầu thành công.');
    }

    /**
     * Approve a wallet request
     *
     * @urlParam id integer required The request ID. Example: 1
     */
    public function approve($id, ApproveWalletRequestAction $action)
    {
        try {
            $walletRequest = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new WalletRequestResource($walletRequest), 'Duyệt yêu cầu thành công.');
    }

    /**
     * Reject a wallet request
     *
     * @urlParam id integer required The request ID. Example: 1
     */
    public function reject(RejectWalletRequestRequest $request, $id, RejectWalletRequestAction $action)
    {
        try {
            $walletRequest = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new WalletRequestResource($walletRequest), 'Từ chối yêu cầu thành công.');
    }

    /**
     * Cancel a wallet request (requester, while still pending)
     *
     * @urlParam id integer required The request ID. Example: 1
     */
    public function cancel($id, CancelWalletRequestAction $action)
    {
        try {
            $walletRequest = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new WalletRequestResource($walletRequest), 'Hủy yêu cầu thành công.');
    }

    /**
     * Complete a wallet request
     *
     * Confirms the money has moved outside the system and writes the wallet
     * ledger entry (deposit credits, withdraw debits).
     *
     * @urlParam id integer required The request ID. Example: 1
     */
    public function complete(CompleteWalletRequestRequest $request, $id, CompleteWalletRequestAction $action)
    {
        try {
            $walletRequest = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new WalletRequestResource($walletRequest), 'Hoàn tất yêu cầu thành công.');
    }
}
