<?php

namespace App\Modules\Finance\Payment\Actions;

use App\Modules\Finance\Payment\Services\PaymentService;

class UpdatePaymentAction
{
    public function handle(...$params)
    {
        return app(PaymentService::class)->update(...$params);
    }
}
