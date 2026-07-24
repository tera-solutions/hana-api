<?php

namespace App\Modules\Finance\SubscriptionPackage\Actions;

use App\Modules\Finance\SubscriptionPackage\Services\SubscriptionPackageService;

class ListSubscriptionPackageAction
{
    public function handle(...$params)
    {
        return app(SubscriptionPackageService::class)->paginate(...$params);
    }
}
