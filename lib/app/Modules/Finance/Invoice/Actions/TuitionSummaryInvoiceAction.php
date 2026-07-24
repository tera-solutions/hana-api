<?php

namespace App\Modules\Finance\Invoice\Actions;

use App\Modules\Finance\Invoice\Services\InvoiceService;

class TuitionSummaryInvoiceAction
{
    public function handle(...$params)
    {
        return app(InvoiceService::class)->summary(...$params);
    }
}
