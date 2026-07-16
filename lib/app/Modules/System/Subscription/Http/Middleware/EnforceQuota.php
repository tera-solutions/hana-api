<?php

namespace App\Modules\System\Subscription\Http\Middleware;

use App\Modules\System\Subscription\Services\SubscriptionGate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Rejects a create action when it would exceed the business's plan quota for
 * the given resource. Runs after auth.tera. Unlimited plans and grandfathered
 * (no-subscription) businesses always pass.
 *
 * Usage: ->middleware('subscription.quota:students')
 */
class EnforceQuota
{
    public function __construct(private SubscriptionGate $gate) {}

    public function handle(Request $request, Closure $next, string $resource)
    {
        $businessId = Auth::guard('api')->user()?->business_id;

        if ($businessId && ! $this->gate->allows($resource, (int) $businessId)) {
            $limit = $this->gate->limit($resource, (int) $businessId);

            return response()->json([
                'success' => false,
                'msg' => 'Đã đạt giới hạn của gói dịch vụ ('.$limit.'). Vui lòng nâng cấp gói để thêm mới.',
                'data' => null,
                'code' => 402,
                'errors' => ['resource' => $resource, 'limit' => $limit],
            ], 200);
        }

        return $next($request);
    }
}
