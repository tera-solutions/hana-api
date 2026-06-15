<?php

namespace App\Modules\Finance\Payment\Actions;

use App\Modules\Finance\Payment\Services\PaymentService;

class CancelPaymentAction
{
    public function handle(...$params)
    {
        return app(PaymentService::class)->cancel(...$params);
    }
}
