<?php

namespace App\Modules\Finance\Promotion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Promotion\Actions\ActivatePromotionAction;
use App\Modules\Finance\Promotion\Actions\ApplyPromotionAction;
use App\Modules\Finance\Promotion\Actions\ClosePromotionAction;
use App\Modules\Finance\Promotion\Actions\CreatePromotionAction;
use App\Modules\Finance\Promotion\Actions\GenerateVouchersAction;
use App\Modules\Finance\Promotion\Actions\GetPromotionAction;
use App\Modules\Finance\Promotion\Actions\ListPromotionAction;
use App\Modules\Finance\Promotion\Actions\PausePromotionAction;
use App\Modules\Finance\Promotion\Actions\UpdatePromotionAction;
use App\Modules\Finance\Promotion\Actions\ValidateVoucherAction;
use App\Modules\Finance\Promotion\Http\Requests\ApplyPromotionRequest;
use App\Modules\Finance\Promotion\Http\Requests\CreatePromotionRequest;
use App\Modules\Finance\Promotion\Http\Requests\GenerateVouchersRequest;
use App\Modules\Finance\Promotion\Http\Requests\UpdatePromotionRequest;
use App\Modules\Finance\Promotion\Http\Requests\ValidateVoucherRequest;
use App\Modules\Finance\Promotion\Http\Resources\PromotionResource;
use App\Modules\Finance\Promotion\Http\Resources\VoucherResource;
use Illuminate\Http\Request;

/**
 * @group Finance - Promotion
 *
 * Manage promotion programmes, their lifecycle, vouchers and the apply engine
 * (promotion.md).
 *
 * @authenticated
 */
class PromotionController extends Controller
{
    /**
     * List promotions
     *
     * @queryParam search string Search by code or name. Example: HANA
     * @queryParam promotion_type string Filter by type. Example: discount
     * @queryParam status string Filter by status. Example: active
     * @queryParam active_on date Running on a given date (Y-m-d). Example: 2026-06-19
     * @queryParam sort_by string Sort column. Example: created_at
     * @queryParam sort_dir string asc|desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListPromotionAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), PromotionResource::class);
    }

    /**
     * Promotion detail
     *
     * @urlParam id integer required The promotion ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"id": 1, "promotion_code": "PROMO000001"}, "code": 200, "errors": null}
     */
    public function detail($id, GetPromotionAction $action)
    {
        return $this->respondSuccess(new PromotionResource($action->handle($id)));
    }

    /**
     * Create promotion
     *
     * @response 200 {"success": true, "msg": "Tạo chương trình khuyến mãi thành công.", "data": {"id": 1, "status": "draft"}, "code": 200, "errors": null}
     */
    public function create(CreatePromotionRequest $request, CreatePromotionAction $action)
    {
        return $this->respondSuccess(new PromotionResource($action->handle($request->validated())), 'Tạo chương trình khuyến mãi thành công.');
    }

    /**
     * Update promotion
     *
     * @urlParam id integer required The promotion ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Cập nhật chương trình thành công.", "data": {"id": 1}, "code": 200, "errors": null}
     */
    public function update(UpdatePromotionRequest $request, $id, UpdatePromotionAction $action)
    {
        try {
            $promotion = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PromotionResource($promotion), 'Cập nhật chương trình thành công.');
    }

    /**
     * Activate promotion
     *
     * @urlParam id integer required The promotion ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Kích hoạt chương trình thành công.", "data": {"id": 1, "status": "active"}, "code": 200, "errors": null}
     */
    public function activate($id, ActivatePromotionAction $action)
    {
        try {
            $promotion = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PromotionResource($promotion), 'Kích hoạt chương trình thành công.');
    }

    /**
     * Pause promotion
     *
     * @urlParam id integer required The promotion ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Tạm ngưng chương trình thành công.", "data": {"id": 1, "status": "paused"}, "code": 200, "errors": null}
     */
    public function pause($id, PausePromotionAction $action)
    {
        try {
            $promotion = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PromotionResource($promotion), 'Tạm ngưng chương trình thành công.');
    }

    /**
     * Close promotion
     *
     * @urlParam id integer required The promotion ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Kết thúc chương trình thành công.", "data": {"id": 1, "status": "closed"}, "code": 200, "errors": null}
     */
    public function close($id, ClosePromotionAction $action)
    {
        try {
            $promotion = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PromotionResource($promotion), 'Kết thúc chương trình thành công.');
    }

    /**
     * Generate vouchers
     *
     * Creates a batch of voucher codes for the promotion (promotion.md §IX).
     *
     * @urlParam id integer required The promotion ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Sinh voucher thành công.", "data": [{"id": 1, "voucher_code": "HANA2026AB"}], "code": 200, "errors": null}
     */
    public function generateVouchers(GenerateVouchersRequest $request, $id, GenerateVouchersAction $action)
    {
        $vouchers = $action->handle($id, $request->validated());

        return $this->respondSuccess(VoucherResource::collection($vouchers), 'Sinh voucher thành công.');
    }

    /**
     * Validate voucher
     *
     * Checks that a voucher code is usable right now (promotion.md §IX).
     *
     * @response 200 {"success": true, "msg": "Voucher hợp lệ.", "data": {"id": 1, "voucher_code": "HANA2026AB", "status": "active"}, "code": 200, "errors": null}
     * @response 200 scenario="Invalid" {"success": false, "msg": "Voucher đã hết hạn.", "data": null, "code": 200, "errors": null}
     */
    public function validateVoucher(ValidateVoucherRequest $request, ValidateVoucherAction $action)
    {
        try {
            $voucher = $action->handle($request->validated()['voucher_code']);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new VoucherResource($voucher), 'Voucher hợp lệ.');
    }

    /**
     * Apply promotion
     *
     * Resolves the promotion (by id or voucher code), computes the discount and records
     * a usage row (promotion.md §X). Does not mutate the invoice.
     *
     * @response 200 {"success": true, "msg": "Áp dụng khuyến mãi thành công.", "data": {"original_amount": 6000000, "discount_amount": 500000, "final_amount": 5500000}, "code": 200, "errors": null}
     * @response 200 scenario="Below minimum" {"success": false, "msg": "Đơn hàng chưa đạt giá trị tối thiểu để áp dụng khuyến mãi.", "data": null, "code": 200, "errors": null}
     */
    public function apply(ApplyPromotionRequest $request, ApplyPromotionAction $action)
    {
        try {
            $result = $action->handle($request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess([
            'promotion' => new PromotionResource($result['promotion']),
            'voucher' => $result['voucher'] ? new VoucherResource($result['voucher']) : null,
            'original_amount' => $result['original_amount'],
            'discount_amount' => $result['discount_amount'],
            'final_amount' => $result['final_amount'],
            'usage_id' => $result['usage']->id,
        ], 'Áp dụng khuyến mãi thành công.');
    }
}
