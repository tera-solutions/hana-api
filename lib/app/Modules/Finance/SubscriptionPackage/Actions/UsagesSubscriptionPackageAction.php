<?php

namespace App\Modules\Finance\SubscriptionPackage\Actions;

use App\Modules\Finance\SubscriptionPackage\Services\SubscriptionPackageService;

class UsagesSubscriptionPackageAction
{
    public function handle(...$params)
    {
        return app(SubscriptionPackageService::class)->usages(...$params);
    }
}
