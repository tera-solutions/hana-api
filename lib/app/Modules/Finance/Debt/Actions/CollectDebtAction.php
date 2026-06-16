<?php

namespace App\Modules\Finance\Debt\Actions;

use App\Modules\Finance\Debt\Services\DebtService;

class CollectDebtAction
{
    public function handle(...$params)
    {
        return app(DebtService::class)->collect(...$params);
    }
}
