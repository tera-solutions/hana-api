<?php

namespace App\Modules\Finance\Payment\Actions;

use App\Modules\Finance\Payment\Services\PaymentService;

class GetPaymentAction
{
    public function handle(...$params)
    {
        return app(PaymentService::class)->detail(...$params);
    }
}
