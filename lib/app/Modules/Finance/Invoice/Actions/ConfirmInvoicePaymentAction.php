<?php

namespace App\Modules\Finance\Invoice\Actions;

use App\Modules\Finance\Invoice\Services\InvoiceService;

class ConfirmInvoicePaymentAction
{
    public function handle(...$params)
    {
        return app(InvoiceService::class)->confirmPayment(...$params);
    }
}
