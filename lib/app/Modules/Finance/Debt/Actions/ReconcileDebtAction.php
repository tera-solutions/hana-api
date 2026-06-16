<?php

namespace App\Modules\Finance\Debt\Actions;

use App\Modules\Finance\Debt\Services\DebtService;

class ReconcileDebtAction
{
    public function handle(...$params)
    {
        return app(DebtService::class)->reconcile(...$params);
    }
}
