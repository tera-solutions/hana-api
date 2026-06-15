<?php

namespace App\Modules\Finance\Payment\Actions;

use App\Modules\Finance\Payment\Services\PaymentService;

class CreatePaymentAction
{
    public function handle(...$params)
    {
        return app(PaymentService::class)->create(...$params);
    }
}
