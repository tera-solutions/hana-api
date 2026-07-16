<?php

namespace App\Modules\System\Subscription\Http\Middleware;

use App\Modules\System\Subscription\Services\SubscriptionGate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Blocks write actions when the business's subscription has lapsed. Runs after
 * auth.tera. Businesses with no subscription are grandfathered (allowed).
 *
 * Usage: ->middleware('subscription.active')
 */
class EnforceActiveSubscription
{
    public function __construct(private SubscriptionGate $gate) {}

    public function handle(Request $request, Closure $next)
    {
        $businessId = Auth::guard('api')->user()?->business_id;

        if ($businessId && ! $this->gate->hasActiveAccess((int) $businessId)) {
            return response()->json([
                'success' => false,
                'msg' => 'Gói dịch vụ đã hết hạn. Vui lòng gia hạn để tiếp tục sử dụng.',
                'data' => null,
                'code' => 402,
                'errors' => [],
            ], 200);
        }

        return $next($request);
    }
}
