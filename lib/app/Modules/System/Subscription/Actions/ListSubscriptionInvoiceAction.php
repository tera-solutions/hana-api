<?php

namespace App\Modules\System\Subscription\Actions;

use App\Modules\System\Subscription\Services\SubscriptionService;

class ListSubscriptionInvoiceAction
{
    public function handle(...$params)
    {
        return app(SubscriptionService::class)->invoices(...$params);
    }
}
