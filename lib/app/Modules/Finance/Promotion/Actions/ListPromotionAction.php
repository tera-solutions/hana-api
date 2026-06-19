<?php

namespace App\Modules\Finance\Promotion\Actions;

use App\Modules\Finance\Promotion\Services\PromotionService;

class ListPromotionAction
{
    public function handle(...$params)
    {
        return app(PromotionService::class)->paginate(...$params);
    }
}
