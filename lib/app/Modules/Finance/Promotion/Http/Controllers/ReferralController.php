<?php

namespace App\Modules\Finance\Promotion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Promotion\Actions\CreateReferralAction;
use App\Modules\Finance\Promotion\Actions\ListReferralAction;
use App\Modules\Finance\Promotion\Actions\RewardReferralAction;
use App\Modules\Finance\Promotion\Http\Requests\CreateReferralRequest;
use App\Modules\Finance\Promotion\Http\Requests\RewardReferralRequest;
use App\Modules\Finance\Promotion\Http\Resources\ReferralResource;
use Illuminate\Http\Request;

/**
 * @group Finance - Referral
 *
 * Manage parent-to-parent referrals and their rewards (promotion.md §XI).
 *
 * @authenticated
 */
class ReferralController extends Controller
{
    /**
     * List referrals
     *
     * @queryParam referrer_parent_id integer Filter by referrer. Example: 1
     * @queryParam status string Filter by status. Example: pending
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListReferralAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), ReferralResource::class);
    }

    /**
     * Create referral
     *
     * @response 200 {"success": true, "msg": "Tạo lượt giới thiệu thành công.", "data": {"id": 1, "status": "pending"}, "code": 200, "errors": null}
     */
    public function create(CreateReferralRequest $request, CreateReferralAction $action)
    {
        return $this->respondSuccess(new ReferralResource($action->handle($request->validated())), 'Tạo lượt giới thiệu thành công.');
    }

    /**
     * Reward referral
     *
     * Marks a pending referral as rewarded once the referred enrollment is paid (BR011).
     *
     * @urlParam id integer required The referral ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Ghi nhận thưởng giới thiệu thành công.", "data": {"id": 1, "status": "rewarded"}, "code": 200, "errors": null}
     */
    public function reward(RewardReferralRequest $request, $id, RewardReferralAction $action)
    {
        try {
            $referral = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ReferralResource($referral), 'Ghi nhận thưởng giới thiệu thành công.');
    }
}
