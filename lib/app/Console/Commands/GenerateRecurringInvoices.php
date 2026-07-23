<?php

namespace App\Console\Commands;

use App\Modules\Finance\Invoice\Services\InvoiceService;
use App\Modules\Finance\InvoiceConfig\Services\InvoiceConfigService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';

    protected $description = "Auto-generate this month's tuition invoices for businesses with recurring billing enabled.";

    public function handle(InvoiceConfigService $configs, InvoiceService $invoices): int
    {
        $result = $configs->generateDueInvoices($invoices);

        $this->info("Billed {$result['invoices']} invoice(s) across {$result['businesses']} business(es).");

        return self::SUCCESS;
    }
}
