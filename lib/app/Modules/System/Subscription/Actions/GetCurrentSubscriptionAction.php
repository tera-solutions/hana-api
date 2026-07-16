<?php

namespace App\Modules\System\Subscription\Actions;

use App\Modules\System\Subscription\Services\SubscriptionService;

class GetCurrentSubscriptionAction
{
    public function handle(...$params)
    {
        return app(SubscriptionService::class)->current(...$params);
    }
}
