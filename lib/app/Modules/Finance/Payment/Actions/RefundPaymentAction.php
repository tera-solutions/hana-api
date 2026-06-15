<?php

namespace App\Modules\Finance\Payment\Actions;

use App\Modules\Finance\Payment\Services\PaymentService;

class RefundPaymentAction
{
    public function handle(...$params)
    {
        return app(PaymentService::class)->refund(...$params);
    }
}
