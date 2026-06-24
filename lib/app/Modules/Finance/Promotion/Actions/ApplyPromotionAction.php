<?php

namespace App\Modules\Finance\Promotion\Actions;

use App\Modules\Finance\Promotion\Services\PromotionApplyService;

class ApplyPromotionAction
{
    public function handle(...$params)
    {
        return app(PromotionApplyService::class)->apply(...$params);
    }
}
