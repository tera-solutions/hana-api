<?php

namespace App\Modules\System\Subscription\Http\Middleware;

use App\Modules\System\Subscription\Services\SubscriptionGate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Blocks a route when the business's plan does not include the given feature.
 * Runs after auth.tera. Unlimited/grandfathered (no-subscription) businesses and
 * packages without a configured feature set always pass.
 *
 * Usage: ->middleware('subscription.feature:assignments')
 */
class EnforceFeature
{
    public function __construct(private SubscriptionGate $gate) {}

    public function handle(Request $request, Closure $next, string $feature)
    {
        $businessId = Auth::guard('api')->user()?->business_id;

        if ($businessId && ! $this->gate->hasFeature($feature, (int) $businessId)) {
            return response()->json([
                'success' => false,
                'msg' => 'Tính năng này thuộc gói cao hơn. Vui lòng nâng cấp gói để sử dụng.',
                'data' => null,
                'code' => 402,
                'errors' => ['feature' => $feature],
            ], 200);
        }

        return $next($request);
    }
}
