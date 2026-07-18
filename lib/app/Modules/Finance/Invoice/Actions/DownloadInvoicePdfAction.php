<?php

namespace App\Modules\Finance\Invoice\Actions;

use App\Modules\Finance\Invoice\Services\InvoiceService;

class DownloadInvoicePdfAction
{
    public function handle(...$params)
    {
        return app(InvoiceService::class)->downloadPdf(...$params);
    }
}
