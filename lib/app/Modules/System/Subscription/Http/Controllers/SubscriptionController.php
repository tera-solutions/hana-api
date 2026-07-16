<?php

namespace App\Modules\System\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Subscription\Actions\GetCurrentSubscriptionAction;
use App\Modules\System\Subscription\Actions\ListSubscriptionInvoiceAction;
use App\Modules\System\Subscription\Actions\UpgradeSubscriptionAction;
use App\Modules\System\Subscription\Http\Requests\UpgradeSubscriptionRequest;
use App\Modules\System\Subscription\Http\Resources\SubscriptionInvoiceResource;
use App\Modules\System\Subscription\Http\Resources\SubscriptionResource;
use Illuminate\Http\Request;

/**
 * @group System - Subscription
 *
 * The acting business's package subscription and billing history.
 *
 * @authenticated
 */
class SubscriptionController extends Controller
{
    public function current(GetCurrentSubscriptionAction $action)
    {
        $subscription = $action->handle();

        return $this->respondSuccess($subscription ? new SubscriptionResource($subscription) : null);
    }

    public function upgrade(UpgradeSubscriptionRequest $request, UpgradeSubscriptionAction $action)
    {
        $subscription = $action->handle($request->validated());

        return $this->respondSuccess(new SubscriptionResource($subscription), 'Nâng cấp gói thành công.');
    }

    public function invoices(Request $request, ListSubscriptionInvoiceAction $action)
    {
        return $this->respondPaginated(
            $action->handle($request->all()),
            SubscriptionInvoiceResource::class,
        );
    }
}
