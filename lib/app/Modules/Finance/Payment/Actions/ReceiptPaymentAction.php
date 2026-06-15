<?php

namespace App\Modules\Finance\Payment\Actions;

use App\Modules\Finance\Payment\Services\PaymentService;

class ReceiptPaymentAction
{
    public function handle(...$params)
    {
        return app(PaymentService::class)->find(...$params);
    }
}
