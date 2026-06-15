<?php

namespace App\Modules\Finance\Debt\Services;

use App\Modules\Finance\Debt\Enums\DebtStatus;
use App\Modules\Finance\Debt\Models\DebtAdjustment;
use App\Modules\Finance\Invoice\Models\Invoice;
use App\Modules\Finance\Invoice\Services\InvoiceService;
use App\Modules\Finance\Payment\Models\Payment;
use App\Modules\Finance\Payment\Models\PaymentAllocation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * Debt is computed from invoices/payments, not stored (debt.md BR-10). This service
 * reads outstanding balances and records adjustments/write-offs against invoices.
 */
class DebtService
{
    use HandlesEntityQueries;

    /** Invoice statuses that still carry collectible/payable debt. */
    private const OPEN_STATUSES = [
        Invoice::STATUS_PENDING,
        Invoice::STATUS_APPROVED,
        Invoice::STATUS_PENDING_PAYMENT,
        Invoice::STATUS_PARTIAL,
    ];

    private const RELATIONS = ['business', 'branch', 'student'];

    /**
     * Outstanding debts (debt.md §V), one row per invoice with a balance.
     */
    public function paginate(array $params = [])
    {
        $query = $this->statusScopedQuery($params['status'] ?? null);

        if (! empty($params['search'])) {
            $query->where('code', 'like', "%{$params['search']}%");
        }

        foreach (['invoice_type', 'partner_type', 'partner_id', 'business_id', 'branch_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['min_amount'])) {
            $query->where('balance_amount', '>=', $params['min_amount']);
        }
        if (! empty($params['max_amount'])) {
            $query->where('balance_amount', '<=', $params['max_amount']);
        }
        if (! empty($params['overdue'])) {
            $query->whereDate('due_date', '<', today());
        }

        $this->applySort($query, $params, ['code', 'invoice_date', 'due_date', 'total', 'balance_amount', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    /**
     * Debt detail: the invoice plus its payment and adjustment history (debt.md §VI).
     */
    public function detail($invoiceId): array
    {
        $invoice = Invoice::with(['business', 'branch', 'student', 'items'])->findOrFail($invoiceId);

        return [
            'invoice' => $invoice,
            'outstanding' => (float) $invoice->balance_amount,
            'overdue_days' => $this->overdueDays($invoice->due_date),
            'debt_status' => $this->debtStatus($invoice),
            'payments' => Payment::where('invoice_id', $invoiceId)->latest()->get(),
            'adjustments' => DebtAdjustment::where('invoice_id', $invoiceId)->latest()->get(),
        ];
    }

    /**
     * Aging report — outstanding grouped by overdue bucket (debt.md §VIII).
     */
    public function aging(array $params = []): array
    {
        $query = Invoice::query()->where('balance_amount', '>', 0)->whereIn('status', self::OPEN_STATUSES);

        foreach (['invoice_type', 'business_id', 'branch_id', 'partner_type'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $buckets = ['current' => 0.0, 'overdue_1_30' => 0.0, 'overdue_31_60' => 0.0, 'overdue_61_90' => 0.0, 'overdue_90_plus' => 0.0];

        foreach ($query->get(['balance_amount', 'due_date']) as $invoice) {
            $days = $this->overdueDays($invoice->due_date);
            $amount = (float) $invoice->balance_amount;

            $bucket = match (true) {
                $days <= 0 => 'current',
                $days <= 30 => 'overdue_1_30',
                $days <= 60 => 'overdue_31_60',
                $days <= 90 => 'overdue_61_90',
                default => 'overdue_90_plus',
            };

            $buckets[$bucket] += $amount;
        }

        $buckets = array_map(fn ($v) => round($v, 2), $buckets);
        $buckets['total'] = round(array_sum($buckets), 2);

        return $buckets;
    }

    /**
     * Debt dashboard totals (debt.md §XIV).
     */
    public function dashboard(array $params = []): array
    {
        $base = fn () => Invoice::query()->where('balance_amount', '>', 0)->whereIn('status', self::OPEN_STATUSES)
            ->when(! empty($params['business_id']), fn ($q) => $q->where('business_id', $params['business_id']))
            ->when(! empty($params['branch_id']), fn ($q) => $q->where('branch_id', $params['branch_id']));

        return [
            'total_receivable' => round((float) $base()->where('invoice_type', Invoice::TYPE_RECEIVABLE)->sum('balance_amount'), 2),
            'total_payable' => round((float) $base()->where('invoice_type', Invoice::TYPE_PAYABLE)->sum('balance_amount'), 2),
            'overdue_amount' => round((float) $base()->whereDate('due_date', '<', today())->sum('balance_amount'), 2),
            'top_debtors' => $this->topPartners($base(), Invoice::TYPE_RECEIVABLE),
            'top_creditors' => $this->topPartners($base(), Invoice::TYPE_PAYABLE),
        ];
    }

    /**
     * Record a correction or late discount, reducing the invoice (debt.md §XI).
     *
     * @throws \RuntimeException
     */
    public function adjust($invoiceId, array $data): array
    {
        return DB::transaction(function () use ($invoiceId, $data) {
            $invoice = Invoice::findOrFail($invoiceId);

            $type = $data['adjustment_type'];
            if ($type === DebtAdjustment::TYPE_WRITE_OFF) {
                throw new \RuntimeException('Vui lòng dùng chức năng xóa nợ cho điều chỉnh loại write_off.');
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0 || $amount > (float) $invoice->total) {
                throw new \RuntimeException('Số tiền điều chỉnh không hợp lệ.');
            }

            $adjustment = DebtAdjustment::create([
                'invoice_id' => $invoice->id,
                'business_id' => $invoice->business_id,
                'adjustment_type' => $type,
                'amount' => $amount,
                'reason' => $data['reason'],
                'status' => DebtAdjustment::STATUS_APPLIED,
                'note' => $data['note'] ?? null,
                'created_by' => $this->actingUserId(),
            ]);

            $this->applyAdjustment($invoice, $amount);

            return ['invoice' => $invoice->fresh(self::RELATIONS), 'adjustment' => $adjustment];
        });
    }

    /**
     * Raise a write-off request; it only takes effect once approved (BR-08).
     *
     * @throws \RuntimeException
     */
    public function writeoff($invoiceId, array $data): DebtAdjustment
    {
        $invoice = Invoice::findOrFail($invoiceId);

        $amount = round((float) ($data['amount'] ?? $invoice->balance_amount), 2);
        if ($amount <= 0 || $amount > (float) $invoice->balance_amount) {
            throw new \RuntimeException('Số tiền xóa nợ không hợp lệ.');
        }

        return DebtAdjustment::create([
            'invoice_id' => $invoice->id,
            'business_id' => $invoice->business_id,
            'adjustment_type' => DebtAdjustment::TYPE_WRITE_OFF,
            'amount' => $amount,
            'reason' => $data['reason'],
            'status' => DebtAdjustment::STATUS_PENDING,
            'note' => $data['note'] ?? null,
            'created_by' => $this->actingUserId(),
        ]);
    }

    /**
     * Approve a pending write-off and apply it to the invoice (debt.md §XII).
     *
     * @throws \RuntimeException
     */
    public function approveWriteoff($adjustmentId): DebtAdjustment
    {
        return DB::transaction(function () use ($adjustmentId) {
            $adjustment = DebtAdjustment::findOrFail($adjustmentId);

            $this->guardPendingWriteoff($adjustment);

            $adjustment->update([
                'status' => DebtAdjustment::STATUS_APPROVED,
                'approved_by' => $this->actingUserId(),
                'approved_at' => now(),
            ]);

            $invoice = Invoice::find($adjustment->invoice_id);
            if ($invoice) {
                $this->applyWriteoff($invoice, (float) $adjustment->amount);
            }

            return $adjustment->fresh();
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function denyWriteoff($adjustmentId, array $data): DebtAdjustment
    {
        $adjustment = DebtAdjustment::findOrFail($adjustmentId);

        $this->guardPendingWriteoff($adjustment);

        $adjustment->update([
            'status' => DebtAdjustment::STATUS_REJECTED,
            'note' => $data['reason'] ?? $adjustment->note,
        ]);

        return $adjustment->fresh();
    }

    /**
     * Collect a debt by recording a payment against the invoice (debt.md §X).
     * Delegates to the invoice/payment pipeline.
     */
    public function collect($invoiceId, array $data): array
    {
        app(InvoiceService::class)->recordPayment($invoiceId, $data);

        return $this->detail($invoiceId);
    }

    /**
     * Reconcile invoices against their confirmed payment allocations (debt.md §XIII):
     * a mismatch means the invoice's paid_amount differs from allocated payments.
     */
    public function reconcile(array $params = []): array
    {
        $query = Invoice::query()->whereIn('status', array_merge(self::OPEN_STATUSES, [Invoice::STATUS_PAID]));

        foreach (['business_id', 'branch_id', 'partner_type', 'partner_id', 'invoice_type'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $invoices = $query->get(['id', 'code', 'paid_amount']);

        // Confirmed allocations summed per invoice in one query (avoids N+1).
        $allocatedByInvoice = PaymentAllocation::query()
            ->join('fin_payments', 'fin_payment_allocations.payment_id', '=', 'fin_payments.id')
            ->where('fin_payments.status', Payment::STATUS_CONFIRMED)
            ->whereIn('fin_payment_allocations.invoice_id', $invoices->pluck('id'))
            ->groupBy('fin_payment_allocations.invoice_id')
            ->selectRaw('fin_payment_allocations.invoice_id as invoice_id, SUM(fin_payment_allocations.allocated_amount) as total')
            ->pluck('total', 'invoice_id');

        $mismatches = [];
        $matched = 0;

        foreach ($invoices as $invoice) {
            $allocated = round((float) ($allocatedByInvoice[$invoice->id] ?? 0), 2);
            $paid = round((float) $invoice->paid_amount, 2);

            if (abs($allocated - $paid) < 0.01) {
                $matched++;

                continue;
            }

            $mismatches[] = [
                'invoice_id' => $invoice->id,
                'code' => $invoice->code,
                'paid_amount' => $paid,
                'allocated_amount' => $allocated,
                'difference' => round($paid - $allocated, 2),
            ];
        }

        return [
            'matched_count' => $matched,
            'mismatch_count' => count($mismatches),
            'mismatches' => $mismatches,
        ];
    }

    // ── Internals ───────────────────────────────────────────────────────────────

    private function statusScopedQuery(?string $status)
    {
        if ($status === DebtStatus::WrittenOff->value) {
            $ids = DebtAdjustment::where('adjustment_type', DebtAdjustment::TYPE_WRITE_OFF)
                ->where('status', DebtAdjustment::STATUS_APPROVED)
                ->pluck('invoice_id');

            return Invoice::query()->whereIn('id', $ids);
        }

        if ($status === DebtStatus::Closed->value) {
            return Invoice::query()->whereIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_CLOSED]);
        }

        $query = Invoice::query()->where('balance_amount', '>', 0)->whereIn('status', self::OPEN_STATUSES);

        if ($status === DebtStatus::Overdue->value) {
            $query->whereDate('due_date', '<', today());
        } elseif ($status === DebtStatus::Current->value) {
            $query->where(fn ($q) => $q->whereNull('due_date')->orWhereDate('due_date', '>=', today()));
        }

        return $query;
    }

    /**
     * Correction/discount: reduce the invoice total and recompute the balance
     * (BR-06/07 — outstanding is never edited directly).
     */
    private function applyAdjustment(Invoice $invoice, float $amount): void
    {
        $total = max(round((float) $invoice->total - $amount, 2), 0);
        $balance = max(round($total - (float) $invoice->paid_amount, 2), 0);

        $invoice->update([
            'total' => $total,
            'balance_amount' => $balance,
            'status' => $balance <= 0 ? Invoice::STATUS_PAID : $invoice->status,
        ]);
    }

    /**
     * Write-off: forgive the outstanding balance and close the debt (debt.md §XII).
     */
    private function applyWriteoff(Invoice $invoice, float $amount): void
    {
        $balance = max(round((float) $invoice->balance_amount - $amount, 2), 0);

        $invoice->update([
            'balance_amount' => $balance,
            'status' => $balance <= 0 ? Invoice::STATUS_CLOSED : $invoice->status,
        ]);
    }

    private function topPartners($query, string $invoiceType): array
    {
        return $query->where('invoice_type', $invoiceType)
            ->whereNotNull('partner_id')
            ->selectRaw('partner_type, partner_id, SUM(balance_amount) as outstanding')
            ->groupBy('partner_type', 'partner_id')
            ->orderByDesc('outstanding')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'partner_type' => $row->partner_type,
                'partner_id' => $row->partner_id,
                'outstanding' => round((float) $row->outstanding, 2),
            ])
            ->all();
    }

    public function debtStatus(Invoice $invoice): string
    {
        if ($invoice->status === Invoice::STATUS_CLOSED) {
            return DebtStatus::WrittenOff->value;
        }
        if ((float) $invoice->balance_amount <= 0) {
            return DebtStatus::Closed->value;
        }

        return $this->overdueDays($invoice->due_date) > 0
            ? DebtStatus::Overdue->value
            : DebtStatus::Current->value;
    }

    public function overdueDays($dueDate): int
    {
        if (! $dueDate) {
            return 0;
        }

        $due = Carbon::parse($dueDate)->startOfDay();

        return $due->lt(today()) ? $due->diffInDays(today()) : 0;
    }

    private function guardPendingWriteoff(DebtAdjustment $adjustment): void
    {
        if ($adjustment->adjustment_type !== DebtAdjustment::TYPE_WRITE_OFF || $adjustment->status !== DebtAdjustment::STATUS_PENDING) {
            throw new \RuntimeException('Yêu cầu xóa nợ không ở trạng thái chờ duyệt.');
        }
    }

    private function actingUserId(): int|string|null
    {
        return Auth::guard('api')->id() ?? Auth::id();
    }
}
