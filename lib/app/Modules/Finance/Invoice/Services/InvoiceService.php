<?php

namespace App\Modules\Finance\Invoice\Services;

use App\Helpers\Task;
use App\Modules\Education\Student\Models\Student;
use App\Modules\Finance\Invoice\Models\Invoice;
use App\Modules\Finance\Invoice\Models\InvoiceHistory;
use App\Modules\Finance\Payment\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class InvoiceService
{
    use HandlesEntityQueries;

    /**
     * Eager loads shared by detail/create/update responses.
     */
    private const RELATIONS = ['business', 'branch', 'student', 'items', 'payments'];

    /**
     * Paginated, filterable list (invoice.md §XII).
     */
    public function paginate(array $params = [])
    {
        $query = Invoice::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        foreach (['invoice_type', 'status', 'partner_type', 'partner_id', 'business_id', 'branch_id', 'student_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['invoice_date_from'])) {
            $query->whereDate('invoice_date', '>=', $params['invoice_date_from']);
        }
        if (! empty($params['invoice_date_to'])) {
            $query->whereDate('invoice_date', '<=', $params['invoice_date_to']);
        }
        if (! empty($params['due_date_from'])) {
            $query->whereDate('due_date', '>=', $params['due_date_from']);
        }
        if (! empty($params['due_date_to'])) {
            $query->whereDate('due_date', '<=', $params['due_date_to']);
        }
        if (! empty($params['overdue'])) {
            $query->whereDate('due_date', '<', now())
                ->where('balance_amount', '>', 0);
        }

        $this->applySort($query, $params, ['code', 'invoice_date', 'due_date', 'total', 'balance_amount', 'status', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    public function find($id): Invoice
    {
        return Invoice::with(self::RELATIONS)->findOrFail($id);
    }

    /**
     * Detail with the full change history (invoice.md §IX).
     */
    public function detail($id): array
    {
        return [
            'invoice' => $this->find($id),
            'histories' => InvoiceHistory::where('invoice_id', $id)->latest()->get(),
        ];
    }

    /**
     * Render an invoice as a downloadable PDF (same data as `detail()`'s `invoice` key).
     */
    public function downloadPdf($id): \Barryvdh\DomPDF\PDF
    {
        $invoice = $this->find($id);

        return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
            ->setPaper('a4');
    }

    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // An enrollment owns a single invoice (enrollment.md auto-billing); reject
            // a second one for the same enrollment so manual and auto paths can't duplicate.
            if (! empty($data['enrollment_id'])) {
                $alreadyInvoiced = Invoice::where('enrollment_id', $data['enrollment_id'])
                    ->where('status', '!=', Invoice::STATUS_CANCELLED)
                    ->exists();

                if ($alreadyInvoiced) {
                    throw new \RuntimeException('Lượt ghi danh này đã có hóa đơn.');
                }
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            $type = $data['invoice_type'] ?? Invoice::TYPE_RECEIVABLE;

            $subtotal = $this->subtotalFromItems($items, (float) ($data['subtotal'] ?? 0));
            $discount = (float) ($data['discount'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);
            $total = round($subtotal - $discount + $tax, 2);

            $invoice = new Invoice($data);
            $invoice->invoice_type = $type;
            $invoice->code = $this->generateCode($invoice->business_id);
            $invoice->subtotal = $subtotal;
            $invoice->discount = $discount;
            $invoice->tax = $tax;
            $invoice->total = $total;
            $invoice->paid_amount = 0;
            $invoice->balance_amount = $total;
            $invoice->status = $data['status'] ?? $this->defaultStatus($type);
            $invoice->invoice_date = $data['invoice_date'] ?? now()->toDateString();
            $invoice->save();

            $this->replaceItems($invoice, $items);

            $this->log($invoice, 'created', null, $invoice->status);

            return $this->find($invoice->id);
        });
    }

    public function update($id, array $data): Invoice
    {
        return DB::transaction(function () use ($id, $data) {
            $invoice = $this->find($id);

            if (! in_array($invoice->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_PENDING], true)) {
                throw new \RuntimeException('Chỉ có thể chỉnh sửa hóa đơn ở trạng thái nháp hoặc chưa thanh toán.');
            }

            // Immutable: identity, type and the payment-derived fields.
            unset(
                $data['id'], $data['code'], $data['business_id'], $data['invoice_type'],
                $data['paid_amount'], $data['balance_amount'], $data['status']
            );

            $items = array_key_exists('items', $data) ? $data['items'] : null;
            unset($data['items']);

            $invoice->fill($data);

            if (is_array($items)) {
                $this->replaceItems($invoice, $items);
                $invoice->subtotal = $this->subtotalFromItems($items, (float) $invoice->subtotal);
            }

            $invoice->total = round((float) $invoice->subtotal - (float) $invoice->discount + (float) $invoice->tax, 2);
            $invoice->balance_amount = round((float) $invoice->total - (float) $invoice->paid_amount, 2);
            $invoice->save();

            $this->log($invoice, 'updated');

            return $this->find($invoice->id);
        });
    }

    /**
     * Approve a payable invoice (invoice.md §IX bước 3).
     *
     * @throws \RuntimeException
     */
    public function approve($id): Invoice
    {
        $invoice = $this->find($id);

        if (! $invoice->isPayable()) {
            throw new \RuntimeException('Chỉ hóa đơn chi cần phê duyệt.');
        }
        if (! in_array($invoice->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_PENDING, Invoice::STATUS_PENDING_PAYMENT], true)) {
            throw new \RuntimeException('Chỉ có thể duyệt hóa đơn ở trạng thái nháp hoặc chờ duyệt.');
        }

        return $this->transition($invoice, Invoice::STATUS_APPROVED, 'approved');
    }

    /**
     * Deny a payable invoice — moves it to cancelled (invoice.md §IX).
     *
     * @throws \RuntimeException
     */
    public function deny($id, array $data): Invoice
    {
        $invoice = $this->find($id);

        if (! $invoice->isPayable()) {
            throw new \RuntimeException('Chỉ hóa đơn chi cần phê duyệt.');
        }
        if (! in_array($invoice->status, [Invoice::STATUS_DRAFT, Invoice::STATUS_PENDING, Invoice::STATUS_PENDING_PAYMENT], true)) {
            throw new \RuntimeException('Chỉ có thể từ chối hóa đơn đang chờ duyệt.');
        }

        return $this->transition($invoice, Invoice::STATUS_CANCELLED, 'denied', $data['reason'] ?? null, $data['note'] ?? null);
    }

    /**
     * @throws \RuntimeException
     */
    public function cancel($id, array $data): Invoice
    {
        $invoice = $this->find($id);

        if (in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED, Invoice::STATUS_REFUNDED, Invoice::STATUS_CLOSED], true)) {
            throw new \RuntimeException('Không thể hủy hóa đơn đã thanh toán, đã hủy hoặc đã hoàn tiền.');
        }

        return $this->transition($invoice, Invoice::STATUS_CANCELLED, 'cancelled', $data['reason'] ?? null, $data['note'] ?? null);
    }

    /**
     * @throws \RuntimeException
     */
    public function refund($id, array $data): Invoice
    {
        $invoice = $this->find($id);

        if ($invoice->status !== Invoice::STATUS_PAID) {
            throw new \RuntimeException('Chỉ có thể hoàn tiền hóa đơn đã thanh toán.');
        }

        return $this->transition($invoice, Invoice::STATUS_REFUNDED, 'refunded', $data['reason'] ?? null, $data['note'] ?? null);
    }

    /**
     * Record a receipt (receivable) or disbursement (payable) against the invoice
     * (invoice.md §X). Payable invoices must be approved first (§IX business rule).
     *
     * @throws \RuntimeException
     */
    public function recordPayment($id, array $data): Invoice
    {
        return DB::transaction(function () use ($id, $data) {
            // Only scalar status/balance are needed to gate the payment; the
            // eager-loaded reload happens once after billing, for the response.
            $invoice = Invoice::findOrFail($id);

            $this->guardPayable($invoice);

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new \RuntimeException('Số tiền thanh toán phải lớn hơn 0.');
            }
            if ($amount > (float) $invoice->balance_amount) {
                throw new \RuntimeException('Số tiền thanh toán vượt quá số còn phải thanh toán.');
            }

            $from = $invoice->status;

            // The Payment module is the single source of cash-flow records: it creates
            // the confirmed payment, allocates it to this invoice and applies the
            // amount to the invoice's paid/balance/status (payment.md §IX).
            app(PaymentService::class)->recordForInvoice($invoice, $data);

            $invoice = $this->find($id);
            $this->log($invoice, 'payment', $from, $invoice->status, null, 'Thanh toán '.$amount);

            if ($invoice->student_id) {
                $this->syncStudentDebtStatus($invoice->student_id);
            }

            return $invoice;
        });
    }

    /**
     * A student with an overdue, unpaid receivable invoice can't stay "active"
     * (đang học) — flips them to "debt" (nợ học phí), and back once cleared.
     * Only touches students currently active or in debt: an explicitly
     * suspended/graduated/dropped student's status means something else and
     * is left alone. Called after a payment; `SyncStudentDebtStatus` (daily
     * command) catches invoices that only just became overdue by elapsed time.
     */
    public function syncStudentDebtStatus(int $studentId): void
    {
        $student = Student::find($studentId);

        if (! $student || ! in_array($student->status, [Student::STATUS_ACTIVE, Student::STATUS_DEBT], true)) {
            return;
        }

        $hasOverdueDebt = Invoice::where('student_id', $studentId)
            ->where('invoice_type', Invoice::TYPE_RECEIVABLE)
            ->whereNotIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_CANCELLED, Invoice::STATUS_REFUNDED])
            ->where('balance_amount', '>', 0)
            ->whereDate('due_date', '<', now())
            ->exists();

        $target = $hasOverdueDebt ? Student::STATUS_DEBT : Student::STATUS_ACTIVE;

        if ($student->status !== $target) {
            $student->update(['status' => $target]);
        }
    }

    /**
     * Enforce the payment-eligibility rules per invoice type.
     *
     * @throws \RuntimeException
     */
    private function guardPayable(Invoice $invoice): void
    {
        if (in_array($invoice->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_REFUNDED, Invoice::STATUS_CLOSED, Invoice::STATUS_PAID], true)) {
            throw new \RuntimeException('Hóa đơn không ở trạng thái có thể thanh toán.');
        }

        $allowed = $invoice->isPayable()
            ? [Invoice::STATUS_APPROVED, Invoice::STATUS_PENDING_PAYMENT, Invoice::STATUS_PARTIAL]
            : [Invoice::STATUS_PENDING, Invoice::STATUS_PARTIAL];

        if (! in_array($invoice->status, $allowed, true)) {
            throw new \RuntimeException($invoice->isPayable()
                ? 'Hóa đơn chi phải được duyệt trước khi thanh toán.'
                : 'Hóa đơn chưa ở trạng thái có thể thanh toán.');
        }
    }

    private function transition(Invoice $invoice, string $to, string $action, ?string $reason = null, ?string $note = null): Invoice
    {
        $from = $invoice->status;
        $invoice->update(['status' => $to]);
        $this->log($invoice, $action, $from, $to, $reason, $note);

        return $this->find($invoice->id);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function subtotalFromItems(array $items, float $fallback): float
    {
        if (empty($items)) {
            return round($fallback, 2);
        }

        return round(array_reduce($items, function (float $carry, array $item) {
            $total = isset($item['total'])
                ? (float) $item['total']
                : (float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0);

            return $carry + $total;
        }, 0.0), 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function replaceItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            $invoice->items()->create([
                'name' => $item['name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => isset($item['total']) ? (float) $item['total'] : round($quantity * $unitPrice, 2),
            ]);
        }
    }

    private function defaultStatus(string $type): string
    {
        return $type === Invoice::TYPE_PAYABLE ? Invoice::STATUS_DRAFT : Invoice::STATUS_PENDING;
    }

    private function generateCode($businessId): string
    {
        $count = Task::setAndGetReferenceCount('invoice', $businessId ?? 0);

        return Task::generateReferenceNumber('invoice', $count, 'INV');
    }

    private function log(Invoice $invoice, string $action, $from = null, $to = null, $reason = null, $note = null): void
    {
        InvoiceHistory::create([
            'invoice_id' => $invoice->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'note' => $note,
            'created_by' => $this->actingUserId(),
        ]);
    }

    private function actingUserId(): int|string|null
    {
        return Auth::guard('api')->id() ?? Auth::id();
    }
}
