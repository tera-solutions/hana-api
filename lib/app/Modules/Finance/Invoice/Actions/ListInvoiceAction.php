<?php

namespace App\Modules\Finance\Invoice\Actions;

use App\Modules\Finance\Invoice\Services\InvoiceService;

class ListInvoiceAction
{
    public function handle(...$params)
    {
        return app(InvoiceService::class)->paginate(...$params);
    }
}
