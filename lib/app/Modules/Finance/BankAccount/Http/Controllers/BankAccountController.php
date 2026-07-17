<?php

namespace App\Modules\Finance\BankAccount\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\BankAccount\Actions\GetMyBankAccountAction;
use App\Modules\Finance\BankAccount\Actions\UpdateMyBankAccountAction;
use App\Modules\Finance\BankAccount\Http\Requests\UpdateBankAccountRequest;
use App\Modules\Finance\BankAccount\Http\Resources\BankAccountResource;
use Illuminate\Support\Facades\Auth;

/**
 * @group Finance - Bank Account
 *
 * Self-service access to the acting teacher's own HR profile bank account —
 * the payout target used by wallet withdraw requests.
 *
 * @authenticated
 */
class BankAccountController extends Controller
{
    /**
     * My bank account
     *
     * @response 200 {"success": true, "msg": null, "data": {"id": 1, "bank_name": "Vietcombank", "bank_account_number": "0123456789", "bank_account_holder": "NGUYEN VAN A", "bank_branch": null}, "code": 200, "errors": null}
     */
    public function me(GetMyBankAccountAction $action)
    {
        $account = $action->handle(
            Auth::guard('api')->user()?->business_id,
            Auth::guard('api')->id(),
        );

        return $this->respondSuccess($account ? new BankAccountResource($account) : null);
    }

    /**
     * Set/update my bank account
     */
    public function update(UpdateBankAccountRequest $request, UpdateMyBankAccountAction $action)
    {
        try {
            $account = $action->handle(
                Auth::guard('api')->user()?->business_id,
                Auth::guard('api')->id(),
                $request->validated(),
            );
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new BankAccountResource($account), 'Cập nhật tài khoản ngân hàng thành công.');
    }
}
