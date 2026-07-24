<?php

namespace App\Modules\Finance\SubscriptionPackage\Actions;

use App\Modules\Finance\SubscriptionPackage\Services\SubscriptionPackageService;

class SummarySubscriptionPackageAction
{
    public function handle(...$params)
    {
        return app(SubscriptionPackageService::class)->summary(...$params);
    }
}
