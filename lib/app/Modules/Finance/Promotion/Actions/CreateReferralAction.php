<?php

namespace App\Modules\Finance\Promotion\Actions;

use App\Modules\Finance\Promotion\Services\ReferralService;

class CreateReferralAction
{
    public function handle(...$params)
    {
        return app(ReferralService::class)->create(...$params);
    }
}
