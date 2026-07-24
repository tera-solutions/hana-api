<?php

namespace App\Modules\Finance\SubscriptionPackage\Actions;

use App\Modules\Finance\SubscriptionPackage\Services\SubscriptionPackageService;

class UpdateSubscriptionPackageAction
{
    public function handle(...$params)
    {
        return app(SubscriptionPackageService::class)->update(...$params);
    }
}
