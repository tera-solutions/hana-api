<?php

namespace App\Modules\Finance\Payment\Services;

use App\Helpers\Task;
use App\Modules\Finance\Account\Models\Account;
use App\Modules\Finance\Invoice\Models\Invoice;
use App\Modules\Finance\Payment\Models\Payment;
use App\Modules\Finance\Payment\Models\PaymentHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class PaymentService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['business', 'branch', 'account', 'invoice', 'allocations'];

    /**
     * Paginated, filterable list (payment.md §XIII).
     */
    public function paginate(array $params = [])
    {
        $query = Payment::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('payment_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        foreach (['payment_direction', 'payment_type', 'status', 'account_id', 'partner_type', 'partner_id', 'business_id', 'branch_id', 'invoice_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['date_from'])) {
            $query->whereDate('payment_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('payment_date', '<=', $params['date_to']);
        }

        $this->applySort($query, $params, ['payment_no', 'payment_date', 'amount', 'status', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    public function find($id): Payment
    {
        return Payment::with(self::RELATIONS)->findOrFail($id);
    }

    public function detail($id): array
    {
        return [
            'payment' => $this->find($id),
            'histories' => PaymentHistory::where('payment_id', $id)->latest()->get(),
        ];
    }

    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $allocations = $data['allocations'] ?? [];
            unset($data['allocations']);

            $payment = $this->buildPayment($data);
            $payment->status = ($data['status'] ?? Payment::STATUS_DRAFT) === Payment::STATUS_PENDING
                ? Payment::STATUS_PENDING
                : Payment::STATUS_DRAFT;
            $payment->save();

            $this->syncAllocations($payment, $allocations);

            $this->log($payment, 'created', null, $payment->status);

            return $this->find($payment->id);
        });
    }

    public function update($id, array $data): Payment
    {
        return DB::transaction(function () use ($id, $data) {
            $payment = $this->find($id);

            if (! in_array($payment->status, [Payment::STATUS_DRAFT, Payment::STATUS_PENDING], true)) {
                throw new \RuntimeException('Chỉ có thể chỉnh sửa giao dịch ở trạng thái nháp hoặc chờ xác nhận.');
            }

            // Immutable identity / confirmation fields (BR-04).
            unset(
                $data['id'], $data['payment_no'], $data['business_id'], $data['payment_direction'],
                $data['status'], $data['confirmed_by'], $data['confirmed_at'], $data['parent_payment_id']
            );

            $allocations = array_key_exists('allocations', $data) ? $data['allocations'] : null;
            unset($data['allocations']);

            $payment->update($data);

            if (is_array($allocations)) {
                $payment->allocations()->delete();
                $this->syncAllocations($payment, $allocations);
            }

            $this->log($payment, 'updated');

            return $this->find($id);
        });
    }

    /**
     * Confirm a payment: move the fund balance and apply allocations to invoices
     * (payment.md §VIII, BR-03). Only confirmed transactions affect balances.
     *
     * @throws \RuntimeException
     */
    public function confirm($id): Payment
    {
        return DB::transaction(function () use ($id) {
            $payment = $this->find($id);

            if (! in_array($payment->status, [Payment::STATUS_DRAFT, Payment::STATUS_PENDING], true)) {
                throw new \RuntimeException('Chỉ có thể xác nhận giao dịch ở trạng thái nháp hoặc chờ xác nhận.');
            }

            $from = $payment->status;
            $payment->update([
                'status' => Payment::STATUS_CONFIRMED,
                'confirmed_by' => $this->actingUserId(),
                'confirmed_at' => now(),
            ]);

            $this->adjustAccount($payment->account_id, $payment->payment_direction, (float) $payment->amount);

            foreach ($payment->allocations as $allocation) {
                $this->applyToInvoice($allocation->invoice_id, (float) $allocation->allocated_amount, 1);
            }

            $this->log($payment, 'confirmed', $from, Payment::STATUS_CONFIRMED);

            return $this->find($id);
        });
    }

    /**
     * Cancel a not-yet-confirmed payment (payment.md §XI; confirmed ones must be
     * reversed/refunded instead — BR-05/06).
     *
     * @throws \RuntimeException
     */
    public function cancel($id, array $data): Payment
    {
        $payment = $this->find($id);

        if ($payment->status === Payment::STATUS_CONFIRMED) {
            throw new \RuntimeException('Không thể hủy giao dịch đã xác nhận; hãy dùng đảo giao dịch hoặc hoàn tiền.');
        }
        if (in_array($payment->status, [Payment::STATUS_CANCELLED, Payment::STATUS_REVERSED, Payment::STATUS_REFUNDED], true)) {
            throw new \RuntimeException('Giao dịch không ở trạng thái có thể hủy.');
        }

        $from = $payment->status;
        $payment->update(['status' => Payment::STATUS_CANCELLED]);
        $this->log($payment, 'cancelled', $from, Payment::STATUS_CANCELLED, $data['reason'] ?? null, $data['note'] ?? null);

        return $this->find($id);
    }

    /**
     * Reverse a confirmed payment by booking an equal, opposite transaction
     * (payment.md §XI, BR-06). The original is never edited.
     *
     * @throws \RuntimeException
     */
    public function reverse($id, array $data): Payment
    {
        return DB::transaction(function () use ($id, $data) {
            $payment = $this->find($id);

            if ($payment->status !== Payment::STATUS_CONFIRMED) {
                throw new \RuntimeException('Chỉ có thể đảo giao dịch đã xác nhận.');
            }

            $counter = $this->makeCounterPayment($payment, (float) $payment->amount, 'Đảo giao dịch '.$payment->payment_no);

            // Undo the original's effect on every allocated invoice.
            foreach ($payment->allocations as $allocation) {
                $this->applyToInvoice($allocation->invoice_id, (float) $allocation->allocated_amount, -1);
            }

            $payment->update(['status' => Payment::STATUS_REVERSED]);
            $this->log($payment, 'reversed', Payment::STATUS_CONFIRMED, Payment::STATUS_REVERSED, $data['reason'] ?? null, 'Giao dịch đảo: '.$counter->payment_no);

            return $this->find($id);
        });
    }

    /**
     * Refund part or all of a confirmed payment via a new opposite transaction
     * (payment.md §X). The original is preserved for audit.
     *
     * @throws \RuntimeException
     */
    public function refund($id, array $data): Payment
    {
        return DB::transaction(function () use ($id, $data) {
            $payment = $this->find($id);

            if ($payment->status !== Payment::STATUS_CONFIRMED) {
                throw new \RuntimeException('Chỉ có thể hoàn tiền giao dịch đã xác nhận.');
            }

            $amount = round((float) ($data['amount'] ?? $payment->amount), 2);
            if ($amount <= 0 || $amount > (float) $payment->amount) {
                throw new \RuntimeException('Số tiền hoàn không hợp lệ.');
            }

            $counter = $this->makeCounterPayment($payment, $amount, 'Hoàn tiền '.$payment->payment_no);

            if ($payment->invoice_id) {
                $this->applyToInvoice($payment->invoice_id, $amount, -1);
            }

            $payment->update(['status' => Payment::STATUS_REFUNDED]);
            $this->log($payment, 'refunded', Payment::STATUS_CONFIRMED, Payment::STATUS_REFUNDED, $data['reason'] ?? null, 'Giao dịch hoàn: '.$counter->payment_no);

            return $this->find($id);
        });
    }

    /**
     * Create a confirmed receipt/disbursement for a single invoice and apply it.
     * Used by InvoiceService::recordPayment() so all cash flow flows through here.
     */
    public function recordForInvoice(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $amount = round((float) $data['amount'], 2);
            $direction = $invoice->isReceivable() ? Payment::DIRECTION_IN : Payment::DIRECTION_OUT;

            $payment = $this->create([
                'business_id' => $invoice->business_id,
                'branch_id' => $invoice->branch_id,
                'student_id' => $invoice->student_id,
                'enrollment_id' => $invoice->enrollment_id,
                'invoice_id' => $invoice->id,
                'account_id' => $data['account_id'] ?? null,
                'payment_direction' => $direction,
                'payment_type' => $data['payment_type'] ?? null,
                'partner_type' => $invoice->partner_type,
                'partner_id' => $invoice->partner_id,
                'amount' => $amount,
                'method' => $data['method'] ?? null,
                'reference_no' => $data['transaction_id'] ?? ($data['reference_no'] ?? null),
                'transaction_id' => $data['transaction_id'] ?? null,
                'note' => $data['note'] ?? null,
                'payment_date' => $data['paid_at'] ?? now()->toDateString(),
                'paid_at' => $data['paid_at'] ?? now(),
                'allocations' => [['invoice_id' => $invoice->id, 'allocated_amount' => $amount]],
            ]);

            return $this->confirm($payment->id);
        });
    }

    // ── Internals ───────────────────────────────────────────────────────────────

    private function buildPayment(array $data): Payment
    {
        $payment = new Payment($data);
        $payment->payment_no = $this->generatePaymentNo($payment->business_id);
        $payment->currency = $data['currency'] ?? 'VND';
        $payment->payment_date = $data['payment_date'] ?? now()->toDateString();

        return $payment;
    }

    /**
     * @param  array<int, array<string, mixed>>  $allocations
     */
    private function syncAllocations(Payment $payment, array $allocations): void
    {
        foreach ($allocations as $allocation) {
            if (empty($allocation['invoice_id'])) {
                continue;
            }

            $payment->allocations()->create([
                'invoice_id' => $allocation['invoice_id'],
                'allocated_amount' => round((float) ($allocation['allocated_amount'] ?? 0), 2),
            ]);
        }
    }

    /**
     * Book an opposite, confirmed transaction mirroring $origin (reverse/refund).
     */
    private function makeCounterPayment(Payment $origin, float $amount, string $description): Payment
    {
        $direction = $origin->payment_direction === Payment::DIRECTION_IN
            ? Payment::DIRECTION_OUT
            : Payment::DIRECTION_IN;

        $counter = $this->buildPayment([
            'business_id' => $origin->business_id,
            'branch_id' => $origin->branch_id,
            'student_id' => $origin->student_id,
            'enrollment_id' => $origin->enrollment_id,
            'invoice_id' => $origin->invoice_id,
            'account_id' => $origin->account_id,
            'payment_direction' => $direction,
            'payment_type' => $origin->payment_type,
            'partner_type' => $origin->partner_type,
            'partner_id' => $origin->partner_id,
            'amount' => $amount,
            'method' => $origin->method,
            'description' => $description,
        ]);
        $counter->parent_payment_id = $origin->id;
        $counter->status = Payment::STATUS_CONFIRMED;
        $counter->confirmed_by = $this->actingUserId();
        $counter->confirmed_at = now();
        $counter->save();

        $this->adjustAccount($counter->account_id, $direction, $amount);
        $this->log($counter, 'created', null, Payment::STATUS_CONFIRMED, null, $description);

        return $counter;
    }

    /**
     * Apply $amount of a confirmed payment to an invoice's running totals
     * ($sign = +1 to pay down, -1 to undo). Mirrors the invoice payment math.
     */
    private function applyToInvoice($invoiceId, float $amount, int $sign): void
    {
        $invoice = Invoice::find($invoiceId);
        if (! $invoice) {
            return;
        }

        $paid = max(round((float) $invoice->paid_amount + $sign * $amount, 2), 0);
        $balance = max(round((float) $invoice->total - $paid, 2), 0);

        if ($paid <= 0) {
            $status = $invoice->isPayable() ? Invoice::STATUS_APPROVED : Invoice::STATUS_PENDING;
            $paidAt = null;
        } elseif ($balance <= 0) {
            $status = Invoice::STATUS_PAID;
            $paidAt = now();
        } else {
            $status = Invoice::STATUS_PARTIAL;
            $paidAt = $invoice->paid_at;
        }

        $invoice->update([
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'status' => $status,
            'paid_at' => $paidAt,
        ]);
    }

    /**
     * Move a fund's balance: IN increases it, OUT decreases it (BR-03).
     */
    private function adjustAccount($accountId, string $direction, float $amount): void
    {
        if (! $accountId) {
            return;
        }

        $account = Account::find($accountId);
        if (! $account) {
            return;
        }

        $delta = $direction === Payment::DIRECTION_IN ? $amount : -$amount;
        $account->update(['balance' => round((float) $account->balance + $delta, 2)]);
    }

    private function generatePaymentNo($businessId): string
    {
        $count = Task::setAndGetReferenceCount('payment', $businessId ?? 0);

        return Task::generateReferenceNumber('payment', $count, 'PAY');
    }

    private function log(Payment $payment, string $action, $from = null, $to = null, $reason = null, $note = null): void
    {
        PaymentHistory::create([
            'payment_id' => $payment->id,
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
