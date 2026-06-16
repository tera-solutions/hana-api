<?php

namespace App\Modules\Finance\Debt\Actions;

use App\Modules\Finance\Debt\Services\DebtService;

class ApproveWriteoffAction
{
    public function handle(...$params)
    {
        return app(DebtService::class)->approveWriteoff(...$params);
    }
}
