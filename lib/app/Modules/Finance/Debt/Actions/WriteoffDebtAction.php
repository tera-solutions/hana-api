<?php

namespace App\Modules\Finance\Debt\Actions;

use App\Modules\Finance\Debt\Services\DebtService;

class WriteoffDebtAction
{
    public function handle(...$params)
    {
        return app(DebtService::class)->writeoff(...$params);
    }
}
