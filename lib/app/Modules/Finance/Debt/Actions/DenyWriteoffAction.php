<?php

namespace App\Modules\Finance\Debt\Actions;

use App\Modules\Finance\Debt\Services\DebtService;

class DenyWriteoffAction
{
    public function handle(...$params)
    {
        return app(DebtService::class)->denyWriteoff(...$params);
    }
}
