<?php

namespace App\Modules\Finance\Promotion\Actions;

use App\Modules\Finance\Promotion\Services\VoucherService;

class ValidateVoucherAction
{
    public function handle(...$params)
    {
        return app(VoucherService::class)->validateCode(...$params);
    }
}
