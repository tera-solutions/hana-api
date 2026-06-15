<?php

namespace App\Modules\Finance\Invoice\Actions;

use App\Modules\Finance\Invoice\Services\InvoiceService;

class CancelInvoiceAction
{
    public function handle(...$params)
    {
        return app(InvoiceService::class)->cancel(...$params);
    }
}
