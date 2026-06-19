<?php

namespace App\Modules\Finance\Promotion\Actions;

use App\Modules\Finance\Promotion\Services\VoucherService;

class GenerateVouchersAction
{
    public function handle(...$params)
    {
        return app(VoucherService::class)->generate(...$params);
    }
}
