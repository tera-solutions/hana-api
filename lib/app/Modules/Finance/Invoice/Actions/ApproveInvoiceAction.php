<?php

namespace App\Modules\Finance\Invoice\Actions;

use App\Modules\Finance\Invoice\Services\InvoiceService;

class ApproveInvoiceAction
{
    public function handle(...$params)
    {
        return app(InvoiceService::class)->approve(...$params);
    }
}
