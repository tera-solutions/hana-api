<?php

namespace App\Modules\Finance\Promotion\Actions;

use App\Modules\Finance\Promotion\Services\ReferralService;

class ListReferralAction
{
    public function handle(...$params)
    {
        return app(ReferralService::class)->paginate(...$params);
    }
}
