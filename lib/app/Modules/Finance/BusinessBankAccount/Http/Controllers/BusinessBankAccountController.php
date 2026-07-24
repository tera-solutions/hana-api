<?php

namespace App\Modules\Finance\BusinessBankAccount\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\BusinessBankAccount\Actions\CreateBusinessBankAccountAction;
use App\Modules\Finance\BusinessBankAccount\Actions\GetBusinessBankAccountAction;
use App\Modules\Finance\BusinessBankAccount\Actions\GetBusinessBankAccountQrAction;
use App\Modules\Finance\BusinessBankAccount\Actions\ListBusinessBankAccountAction;
use App\Modules\Finance\BusinessBankAccount\Actions\RestoreBusinessBankAccountAction;
use App\Modules\Finance\BusinessBankAccount\Actions\SetDefaultBusinessBankAccountAction;
use App\Modules\Finance\BusinessBankAccount\Actions\SuspendBusinessBankAccountAction;
use App\Modules\Finance\BusinessBankAccount\Actions\UpdateBusinessBankAccountAction;
use App\Modules\Finance\BusinessBankAccount\Http\Requests\CreateBusinessBankAccountRequest;
use App\Modules\Finance\BusinessBankAccount\Http\Requests\UpdateBusinessBankAccountRequest;
use App\Modules\Finance\BusinessBankAccount\Http\Resources\BusinessBankAccountResource;
use Illuminate\Http\Request;

/**
 * @group Finance - Business Bank Account
 *
 * The business's own bank accounts for receiving tuition payments — used to
 * build the VietQR payment QR shown on an invoice. Distinct from
 * `/v1/fin/bank-account/me` (teacher/staff payout accounts).
 *
 * @authenticated
 */
class BusinessBankAccountController extends Controller
{
    /**
     * List business bank accounts
     *
     * @queryParam search string Search by bank name, account number or holder. Example: MB
     * @queryParam status string Filter: active|inactive. Example: active
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     */
    public function list(Request $request, ListBusinessBankAccountAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), BusinessBankAccountResource::class);
    }

    /**
     * Business bank account detail
     *
     * @urlParam id integer required The account ID. Example: 1
     */
    public function detail($id, GetBusinessBankAccountAction $action)
    {
        return $this->respondSuccess(new BusinessBankAccountResource($action->handle($id)));
    }

    /**
     * Create business bank account
     */
    public function create(CreateBusinessBankAccountRequest $request, CreateBusinessBankAccountAction $action)
    {
        return $this->respondSuccess(
            new BusinessBankAccountResource($action->handle($request->validated())),
            'Tạo tài khoản ngân hàng thành công.',
        );
    }

    /**
     * Update business bank account
     *
     * @urlParam id integer required The account ID. Example: 1
     */
    public function update(UpdateBusinessBankAccountRequest $request, $id, UpdateBusinessBankAccountAction $action)
    {
        return $this->respondSuccess(
            new BusinessBankAccountResource($action->handle($id, $request->validated())),
            'Cập nhật tài khoản ngân hàng thành công.',
        );
    }

    /**
     * Suspend business bank account
     *
     * @urlParam id integer required The account ID. Example: 1
     */
    public function suspend($id, SuspendBusinessBankAccountAction $action)
    {
        try {
            $account = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new BusinessBankAccountResource($account), 'Ngừng sử dụng tài khoản thành công.');
    }

    /**
     * Restore business bank account
     *
     * @urlParam id integer required The account ID. Example: 1
     */
    public function restore($id, RestoreBusinessBankAccountAction $action)
    {
        try {
            $account = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new BusinessBankAccountResource($account), 'Khôi phục tài khoản thành công.');
    }

    /**
     * Set as default
     *
     * @urlParam id integer required The account ID. Example: 1
     */
    public function setDefault($id, SetDefaultBusinessBankAccountAction $action)
    {
        try {
            $account = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new BusinessBankAccountResource($account), 'Đặt tài khoản mặc định thành công.');
    }

    /**
     * QR preview (static, no amount)
     *
     * @urlParam id integer required The account ID. Example: 1
     */
    public function qr($id, GetBusinessBankAccountQrAction $action)
    {
        return $this->respondSuccess($action->handle($id));
    }
}
