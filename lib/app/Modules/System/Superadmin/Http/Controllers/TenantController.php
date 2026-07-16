<?php

namespace App\Modules\System\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Subscription\Http\Resources\SubscriptionInvoiceResource;
use App\Modules\System\Subscription\Http\Resources\SubscriptionResource;
use App\Modules\System\Superadmin\Http\Requests\AssignPlanRequest;
use App\Modules\System\Superadmin\Http\Requests\ExtendSubscriptionRequest;
use App\Modules\System\Superadmin\Http\Resources\TenantResource;
use App\Modules\System\Superadmin\Services\PlatformSubscriptionService;
use App\Modules\System\Superadmin\Services\TenantManagementService;
use Illuminate\Http\Request;

/**
 * @group System - Superadmin / Tenants
 *
 * Platform-wide tenant and subscription management. Restricted to superadmins;
 * these endpoints deliberately read and write across tenant boundaries.
 *
 * @authenticated
 */
class TenantController extends Controller
{
    /**
     * List tenants
     *
     * @queryParam search string Search by name, email, phone or code. Example: Hana
     * @queryParam status string Filter by status: active, inactive, suspended. Example: active
     * @queryParam sort_by string Sort column: name, created_at, status. Example: created_at
     * @queryParam sort_dir string asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size (default 20). Example: 20
     */
    public function list(Request $request, TenantManagementService $service)
    {
        return $this->respondPaginated($service->paginate($request->all()), TenantResource::class);
    }

    /**
     * Tenant detail
     *
     * Business profile plus usage statistics, the current subscription and
     * recent billing history.
     *
     * @urlParam id integer required The business id. Example: 1
     */
    public function detail($id, TenantManagementService $service)
    {
        $result = $service->detail((int) $id);

        return $this->respondSuccess([
            'business' => new TenantResource($result['business']),
            'statistics' => $result['statistics'],
            'subscription' => $result['subscription']
                ? new SubscriptionResource($result['subscription'])
                : null,
            'invoices' => SubscriptionInvoiceResource::collection($result['invoices']),
        ]);
    }

    /**
     * Suspend a tenant
     *
     * Blocks every user of the tenant from the API until reactivated.
     *
     * @urlParam id integer required The business id. Example: 1
     */
    public function suspend($id, TenantManagementService $service)
    {
        return $this->respondSuccess(
            new TenantResource($service->suspend((int) $id)),
            'Đã tạm ngưng tenant.',
        );
    }

    /**
     * Reactivate a tenant
     *
     * @urlParam id integer required The business id. Example: 1
     */
    public function activate($id, TenantManagementService $service)
    {
        return $this->respondSuccess(
            new TenantResource($service->activate((int) $id)),
            'Đã kích hoạt lại tenant.',
        );
    }

    /**
     * Assign / switch plan
     *
     * Cancels the tenant's current active subscription and starts the chosen
     * package, recording a paid invoice for the change.
     *
     * @urlParam id integer required The business id. Example: 1
     */
    public function assignPlan(AssignPlanRequest $request, $id, PlatformSubscriptionService $service)
    {
        $subscription = $service->assign((int) $id, $request->validated());

        return $this->respondSuccess(new SubscriptionResource($subscription), 'Đã gán gói cho tenant.');
    }

    /**
     * Extend subscription
     *
     * @urlParam id integer required The business id. Example: 1
     */
    public function extendSubscription(ExtendSubscriptionRequest $request, $id, PlatformSubscriptionService $service)
    {
        try {
            $subscription = $service->extend((int) $id, (int) $request->validated()['months']);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new SubscriptionResource($subscription), 'Đã gia hạn gói dịch vụ.');
    }

    /**
     * Cancel subscription
     *
     * @urlParam id integer required The business id. Example: 1
     */
    public function cancelSubscription($id, PlatformSubscriptionService $service)
    {
        try {
            $subscription = $service->cancel((int) $id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new SubscriptionResource($subscription), 'Đã hủy gói dịch vụ.');
    }
}
