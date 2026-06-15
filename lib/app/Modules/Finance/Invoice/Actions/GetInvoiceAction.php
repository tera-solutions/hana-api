<?php

namespace App\Modules\Finance\Invoice\Actions;

use App\Modules\Finance\Invoice\Services\InvoiceService;

class GetInvoiceAction
{
    public function handle(...$params)
    {
        return app(InvoiceService::class)->detail(...$params);
    }
}
