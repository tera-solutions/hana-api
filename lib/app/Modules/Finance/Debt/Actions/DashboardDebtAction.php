<?php

namespace App\Modules\Finance\Debt\Actions;

use App\Modules\Finance\Debt\Services\DebtService;

class DashboardDebtAction
{
    public function handle(...$params)
    {
        return app(DebtService::class)->dashboard(...$params);
    }
}
