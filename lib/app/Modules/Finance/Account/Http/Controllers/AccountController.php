<?php

namespace App\Modules\Finance\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Account\Actions\CreateAccountAction;
use App\Modules\Finance\Account\Actions\GetAccountAction;
use App\Modules\Finance\Account\Actions\ListAccountAction;
use App\Modules\Finance\Account\Actions\RestoreAccountAction;
use App\Modules\Finance\Account\Actions\SuspendAccountAction;
use App\Modules\Finance\Account\Actions\UpdateAccountAction;
use App\Modules\Finance\Account\Http\Requests\CreateAccountRequest;
use App\Modules\Finance\Account\Http\Requests\UpdateAccountRequest;
use App\Modules\Finance\Account\Http\Resources\AccountResource;
use Illuminate\Http\Request;

/**
 * @group Finance - Account
 *
 * Manage funds (quỹ) — cash, bank, e-wallet — whose balances move with confirmed
 * payments (payment.md §VI).
 *
 * @authenticated
 */
class AccountController extends Controller
{
    /**
     * List accounts
     *
     * @queryParam search string Search by code, name or account number. Example: ACC
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam branch_id integer Filter by branch id. Example: 1
     * @queryParam type string Filter: cash|bank|ewallet. Example: cash
     * @queryParam status string Filter: active|inactive. Example: active
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListAccountAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), AccountResource::class);
    }

    /**
     * Account detail
     *
     * @urlParam id integer required The account ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"id": 1, "code": "ACC2026/000001"}, "code": 200, "errors": null}
     */
    public function detail($id, GetAccountAction $action)
    {
        return $this->respondSuccess(new AccountResource($action->handle($id)));
    }

    /**
     * Create account
     *
     * @response 200 {"success": true, "msg": "Tạo quỹ thành công.", "data": {"id": 1, "code": "ACC2026/000001", "type": "cash", "balance": "0.00"}, "code": 200, "errors": null}
     */
    public function create(CreateAccountRequest $request, CreateAccountAction $action)
    {
        return $this->respondSuccess(new AccountResource($action->handle($request->validated())), 'Tạo quỹ thành công.');
    }

    /**
     * Update account
     *
     * Code, business and balance are immutable.
     *
     * @urlParam id integer required The account ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Cập nhật quỹ thành công.", "data": {"id": 1}, "code": 200, "errors": null}
     */
    public function update(UpdateAccountRequest $request, $id, UpdateAccountAction $action)
    {
        return $this->respondSuccess(new AccountResource($action->handle($id, $request->validated())), 'Cập nhật quỹ thành công.');
    }

    /**
     * Suspend account
     *
     * @urlParam id integer required The account ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Ngừng quỹ thành công.", "data": {"id": 1, "status": "inactive"}, "code": 200, "errors": null}
     */
    public function suspend($id, SuspendAccountAction $action)
    {
        try {
            $account = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new AccountResource($account), 'Ngừng quỹ thành công.');
    }

    /**
     * Restore account
     *
     * @urlParam id integer required The account ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Khôi phục quỹ thành công.", "data": {"id": 1, "status": "active"}, "code": 200, "errors": null}
     */
    public function restore($id, RestoreAccountAction $action)
    {
        try {
            $account = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new AccountResource($account), 'Khôi phục quỹ thành công.');
    }
}
