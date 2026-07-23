<?php

namespace App\Console\Commands;

use App\Modules\Education\Student\Models\Student;
use App\Modules\Finance\Invoice\Models\Invoice;
use App\Modules\Finance\Invoice\Services\InvoiceService;
use Illuminate\Console\Command;

/**
 * Catches invoices that only just became overdue with the passage of time
 * (InvoiceService::syncStudentDebtStatus runs synchronously after a payment,
 * but nothing pushes a status change purely because a due_date elapsed).
 */
class SyncStudentDebtStatus extends Command
{
    protected $signature = 'students:sync-debt-status';

    protected $description = "Flip students to/from 'debt' status based on overdue, unpaid receivable invoices.";

    public function handle(InvoiceService $invoices): int
    {
        $studentIds = Invoice::where('invoice_type', Invoice::TYPE_RECEIVABLE)
            ->where('balance_amount', '>', 0)
            ->whereDate('due_date', '<', now())
            ->whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_CANCELLED, Invoice::STATUS_REFUNDED])
            ->whereNotNull('student_id')
            ->distinct()
            ->pluck('student_id');

        // Also re-check students currently in debt whose invoices may have
        // since been cancelled/refunded (not just paid) — recordPayment()
        // already handles the paid path, this covers the rest.
        $debtStudentIds = Student::where('status', Student::STATUS_DEBT)->pluck('id');

        $allIds = $studentIds->merge($debtStudentIds)->unique();

        foreach ($allIds as $studentId) {
            $invoices->syncStudentDebtStatus((int) $studentId);
        }

        $this->info("Checked {$allIds->count()} student(s) for debt status.");

        return self::SUCCESS;
    }
}
