<?php

namespace App\Modules\Finance\Debt\Actions;

use App\Modules\Finance\Debt\Services\DebtService;

class GetDebtAction
{
    public function handle(...$params)
    {
        return app(DebtService::class)->detail(...$params);
    }
}
