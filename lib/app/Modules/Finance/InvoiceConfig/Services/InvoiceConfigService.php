<?php

namespace App\Modules\Finance\InvoiceConfig\Services;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\Finance\Invoice\Models\Invoice;
use App\Modules\Finance\Invoice\Services\InvoiceService;
use App\Modules\Finance\InvoiceConfig\Models\InvoiceConfig;
use App\Modules\System\Business\Models\Business;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceConfigService
{
    /**
     * Read the business's config, defaulting to "off" when none was ever set.
     */
    public function get(int $businessId): InvoiceConfig
    {
        return InvoiceConfig::firstOrNew(
            ['business_id' => $businessId],
            ['auto_generate' => false, 'billing_day' => 1, 'due_days' => 7],
        );
    }

    public function update(int $businessId, array $data): InvoiceConfig
    {
        $reminder = $data['reminder'] ?? [];

        $config = InvoiceConfig::updateOrCreate(['business_id' => $businessId], [
            'auto_generate' => $data['auto_generate'],
            'billing_day' => $data['billing_day'],
            'due_days' => $data['due_days'],
            'late_fee_enabled' => $data['late_fee_enabled'] ?? false,
            'late_fee_percent' => $data['late_fee_percent'] ?? null,
            'unpaid_student_status' => $data['unpaid_student_status'] ?? null,
            'reminder_before_due_days' => $reminder['before_due_days'] ?? null,
            'reminder_on_overdue' => $reminder['on_overdue'] ?? false,
            'reminder_channels' => $reminder['channels'] ?? [],
        ]);

        return $config->fresh();
    }

    /**
     * Force-run recurring billing for one business today, regardless of
     * whether its `billing_day` matches — the FE's "chạy thử ngay" button.
     *
     * @return array{invoices_created: int, period: string}
     */
    public function generateNow(InvoiceService $invoices, int $businessId): array
    {
        $config = $this->get($businessId);
        $today = now();

        $count = $this->generateForBusiness($invoices, $config, $today);

        return ['invoices_created' => $count, 'period' => $today->format('m/Y')];
    }

    /**
     * Bill every active (studying) enrollment of every business whose
     * `billing_day` matches today, for sessions that fell in the current
     * calendar month. Idempotent per (student, class, month) via a marker
     * left in the invoice note — `enrollment_id` is deliberately NOT set,
     * since InvoiceService::create() allows only one non-cancelled invoice
     * per enrollment ever (a one-time-tuition assumption that doesn't fit
     * recurring billing).
     *
     * @return array{businesses: int, invoices: int}
     */
    public function generateDueInvoices(InvoiceService $invoices, ?Carbon $today = null): array
    {
        $today = $today ?? now();
        $daysInMonth = $today->daysInMonth;

        $dueConfigs = InvoiceConfig::where('auto_generate', true)
            ->get()
            ->filter(fn (InvoiceConfig $c) => min($c->billing_day, $daysInMonth) === $today->day);

        $invoiceCount = 0;

        foreach ($dueConfigs as $config) {
            $invoiceCount += $this->generateForBusiness($invoices, $config, $today);
        }

        return ['businesses' => $dueConfigs->count(), 'invoices' => $invoiceCount];
    }

    private function generateForBusiness(InvoiceService $invoices, InvoiceConfig $config, Carbon $today): int
    {
        $business = Business::find($config->business_id);
        if (! $business) {
            return 0;
        }

        $period = $today->format('Y-m');
        $marker = "[AUTO:{$period}]";
        $count = 0;

        Enrollment::where('status', Enrollment::STATUS_STUDYING)
            ->whereHas('student', fn ($q) => $q->where('business_id', $config->business_id))
            ->with('student')
            ->chunk(100, function ($enrollments) use ($invoices, $config, $today, $marker, $period, &$count) {
                foreach ($enrollments as $enrollment) {
                    if ($this->createIfDue($invoices, $enrollment, $config, $today, $marker, $period)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function createIfDue(
        InvoiceService $invoices,
        Enrollment $enrollment,
        InvoiceConfig $config,
        Carbon $today,
        string $marker,
        string $period,
    ): bool {
        return DB::transaction(function () use ($invoices, $enrollment, $config, $today, $marker, $period) {
            $student = $enrollment->student;
            if (! $student) {
                return false;
            }

            $alreadyBilled = Invoice::where('student_id', $student->id)
                ->where('note', 'like', "%{$marker}(class:{$enrollment->class_id})%")
                ->exists();
            if ($alreadyBilled) {
                return false;
            }

            $sessionCount = ClassSession::where('class_id', $enrollment->class_id)
                ->whereYear('session_date', $today->year)
                ->whereMonth('session_date', $today->month)
                ->where('status', '!=', ClassSession::STATUS_CANCELLED)
                ->count();

            if ($sessionCount === 0) {
                return false;
            }

            $pricePerLesson = (float) $enrollment->price_per_lesson;

            $invoices->create([
                'invoice_type' => Invoice::TYPE_RECEIVABLE,
                'business_id' => $student->business_id,
                'branch_id' => $student->branch_id,
                'partner_type' => 'student',
                'partner_id' => $student->id,
                'student_id' => $student->id,
                'invoice_date' => $today->toDateString(),
                'due_date' => $today->clone()->addDays($config->due_days)->toDateString(),
                'note' => "Học phí tự động tháng {$period} {$marker}(class:{$enrollment->class_id})",
                'items' => [[
                    'name' => "Học phí tháng {$period}",
                    'quantity' => $sessionCount,
                    'unit_price' => $pricePerLesson,
                ]],
            ]);

            return true;
        });
    }
}
