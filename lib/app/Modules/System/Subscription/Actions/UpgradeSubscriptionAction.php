<?php

namespace App\Modules\System\Subscription\Actions;

use App\Modules\System\Subscription\Services\SubscriptionService;

class UpgradeSubscriptionAction
{
    public function handle(...$params)
    {
        return app(SubscriptionService::class)->upgrade(...$params);
    }
}
