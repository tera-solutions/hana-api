<?php

namespace App\Modules\Finance\Invoice\Actions;

use App\Modules\Finance\Invoice\Services\InvoiceService;

class RefundInvoiceAction
{
    public function handle(...$params)
    {
        return app(InvoiceService::class)->refund(...$params);
    }
}
