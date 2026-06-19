<?php

namespace App\Modules\Finance\Promotion\Actions;

use App\Modules\Finance\Promotion\Services\ReferralService;

class RewardReferralAction
{
    public function handle(...$params)
    {
        return app(ReferralService::class)->reward(...$params);
    }
}
