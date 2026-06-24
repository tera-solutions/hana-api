<?php

namespace App\Modules\Finance\Wallet\Services;

use App\Modules\Finance\Wallet\Models\Wallet;
use App\Modules\Finance\Wallet\Models\WalletAdjustment;
use App\Modules\Finance\Wallet\Models\WalletTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * All wallet business logic (wallet.md). Balances live in three buckets (available /
 * bonus / frozen); spending draws bonus before available (BR007). Every change writes a
 * ledger entry with the before/after spendable balance (BR004/BR005), guarding amount
 * (BR003), non-negative balance (BR006) and the locked state (BR012).
 */
class WalletService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = Wallet::query();

        foreach (['business_id', 'owner_type', 'owner_id', 'status', 'currency'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['balance_from'])) {
            $query->where('available_balance', '>=', $params['balance_from']);
        }
        if (! empty($params['balance_to'])) {
            $query->where('available_balance', '<=', $params['balance_to']);
        }

        $this->applySort($query, $params, ['available_balance', 'bonus_balance', 'status', 'created_at']);

        return $query->paginate($this->resolvePerPage($params));
    }

    public function find($id): Wallet
    {
        return Wallet::with(['transactions' => fn ($q) => $q->limit(20)])->findOrFail($id);
    }

    public function transactions(array $params = [])
    {
        $query = WalletTransaction::query();

        foreach (['wallet_id', 'transaction_type', 'reference_type', 'reference_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        $this->applySort($query, $params, ['amount', 'transaction_type', 'created_at']);

        return $query->paginate($this->resolvePerPage($params));
    }

    /**
     * Create the single wallet for an owner, or return the existing one (BR001/BR002).
     */
    public function createForOwner(int $businessId, string $ownerType, int $ownerId, array $attributes = []): Wallet
    {
        return Wallet::firstOrCreate(
            ['business_id' => $businessId, 'owner_type' => $ownerType, 'owner_id' => $ownerId],
            array_merge([
                'wallet_code' => 'WAL'.strtoupper(Str::random(10)),
                'available_balance' => 0,
                'bonus_balance' => 0,
                'frozen_balance' => 0,
                'currency' => 'VND',
                'status' => Wallet::STATUS_ACTIVE,
            ], $attributes)
        );
    }

    /**
     * @throws \RuntimeException
     */
    public function lock($id): Wallet
    {
        $wallet = Wallet::findOrFail($id);

        if ($wallet->status === Wallet::STATUS_CLOSED) {
            throw new \RuntimeException('Không thể khóa ví đã đóng.');
        }

        $wallet->update(['status' => Wallet::STATUS_LOCKED]);

        return $wallet->fresh();
    }

    /**
     * @throws \RuntimeException
     */
    public function unlock($id): Wallet
    {
        $wallet = Wallet::findOrFail($id);

        if (! $wallet->isLocked()) {
            throw new \RuntimeException('Ví không ở trạng thái khóa.');
        }

        $wallet->update(['status' => Wallet::STATUS_ACTIVE]);

        return $wallet->fresh();
    }

    public function deposit(array $data): WalletTransaction
    {
        return $this->credit($data, WalletTransaction::TYPE_DEPOSIT, $data['note'] ?? 'Nạp tiền');
    }

    public function payment(array $data): WalletTransaction
    {
        $reference = ! empty($data['invoice_id'])
            ? ['reference_type' => WalletTransaction::REF_INVOICE, 'reference_id' => $data['invoice_id']]
            : [];

        return $this->debit($data, WalletTransaction::TYPE_PAYMENT, $data['note'] ?? 'Thanh toán', $reference);
    }

    public function recordFromInvoice(array $data): WalletTransaction
    {
        return $this->debit($data, WalletTransaction::TYPE_PAYMENT, $data['note'] ?? 'Thanh toán hóa đơn', [
            'reference_type' => WalletTransaction::REF_INVOICE,
            'reference_id' => $data['invoice_id'],
        ]);
    }

    public function recordFromPayment(array $data): WalletTransaction
    {
        return $this->credit($data, WalletTransaction::TYPE_DEPOSIT, $data['note'] ?? 'Ghi nhận từ đơn thanh toán', [
            'reference_type' => WalletTransaction::REF_PAYMENT,
            'reference_id' => $data['payment_id'],
        ]);
    }

    /**
     * @throws \RuntimeException
     */
    public function refund(array $data): WalletTransaction
    {
        return DB::transaction(function () use ($data) {
            $wallet = Wallet::lockForUpdate()->findOrFail($data['wallet_id']);
            $this->assertNotLocked($wallet);
            $amount = $this->assertPositive($data['amount']);

            $original = WalletTransaction::where('wallet_id', $wallet->id)->findOrFail($data['reference_transaction_id']);

            if ($original->transaction_type !== WalletTransaction::TYPE_PAYMENT) {
                throw new \RuntimeException('Chỉ có thể hoàn tiền cho giao dịch thanh toán.'); // BR008
            }

            $alreadyRefunded = (float) WalletTransaction::where('reference_type', WalletTransaction::REF_TRANSACTION)
                ->where('reference_id', $original->id)
                ->where('transaction_type', WalletTransaction::TYPE_REFUND)
                ->sum('amount');

            if ($amount > (float) $original->amount - $alreadyRefunded) {
                throw new \RuntimeException('Số tiền hoàn vượt quá số đã thanh toán.'); // BR009
            }

            $before = $wallet->spendableBalance();
            $wallet->update(['available_balance' => (float) $wallet->available_balance + $amount]);

            return $this->record($wallet, WalletTransaction::TYPE_REFUND, $amount, $before, $before + $amount, [
                'reference_type' => WalletTransaction::REF_TRANSACTION,
                'reference_id' => $original->id,
                'description' => $data['note'] ?? 'Hoàn tiền',
            ]);
        });
    }

    /**
     * Manual increase/decrease — records both an adjustment row and a ledger entry
     * (BR010 reason, BR011 audit).
     *
     * @throws \RuntimeException
     */
    public function adjust(array $data): WalletTransaction
    {
        return DB::transaction(function () use ($data) {
            $wallet = Wallet::lockForUpdate()->findOrFail($data['wallet_id']);
            $amount = $this->assertPositive($data['amount']);
            $increase = $data['adjustment_type'] === WalletAdjustment::TYPE_INCREASE;

            $before = $wallet->spendableBalance();

            if ($increase) {
                $wallet->update(['available_balance' => (float) $wallet->available_balance + $amount]);
            } else {
                if ($amount > (float) $wallet->available_balance) {
                    throw new \RuntimeException('Số dư khả dụng không đủ để điều chỉnh giảm.'); // BR006
                }
                $wallet->update(['available_balance' => (float) $wallet->available_balance - $amount]);
            }

            WalletAdjustment::create([
                'wallet_id' => $wallet->id,
                'adjustment_type' => $data['adjustment_type'],
                'amount' => $amount,
                'reason' => $data['reason'], // BR010
                'approved_by' => Auth::id(),
            ]);

            return $this->record($wallet, WalletTransaction::TYPE_ADJUSTMENT, $amount, $before, $increase ? $before + $amount : $before - $amount, [
                'description' => $data['reason'],
            ]);
        });
    }

    /**
     * Credit the available balance and append a ledger entry.
     *
     * @param  array<string, mixed>  $reference
     */
    private function credit(array $data, string $type, string $description, array $reference = []): WalletTransaction
    {
        return DB::transaction(function () use ($data, $type, $description, $reference) {
            $wallet = Wallet::lockForUpdate()->findOrFail($data['wallet_id']);
            $this->assertNotLocked($wallet);
            $amount = $this->assertPositive($data['amount']);

            $before = $wallet->spendableBalance();
            $wallet->update(['available_balance' => (float) $wallet->available_balance + $amount]);

            return $this->record($wallet, $type, $amount, $before, $before + $amount, $reference + ['description' => $description]);
        });
    }

    /**
     * Debit the spendable balance, drawing bonus before available (BR007).
     *
     * @param  array<string, mixed>  $reference
     *
     * @throws \RuntimeException
     */
    private function debit(array $data, string $type, string $description, array $reference = []): WalletTransaction
    {
        return DB::transaction(function () use ($data, $type, $description, $reference) {
            $wallet = Wallet::lockForUpdate()->findOrFail($data['wallet_id']);
            $this->assertNotLocked($wallet);
            $amount = $this->assertPositive($data['amount']);

            $before = $wallet->spendableBalance();

            if ($amount > $before) {
                throw new \RuntimeException('Số dư ví không đủ.'); // BR006
            }

            $fromBonus = min($amount, (float) $wallet->bonus_balance); // BR007
            $fromAvailable = $amount - $fromBonus;

            $wallet->update([
                'bonus_balance' => (float) $wallet->bonus_balance - $fromBonus,
                'available_balance' => (float) $wallet->available_balance - $fromAvailable,
            ]);

            return $this->record($wallet, $type, $amount, $before, $before - $amount, $reference + ['description' => $description]);
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function record(Wallet $wallet, string $type, float $amount, float $before, float $after, array $extra = []): WalletTransaction
    {
        $transaction = WalletTransaction::create(array_merge([
            'business_id' => $wallet->business_id,
            'wallet_id' => $wallet->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'created_by' => Auth::id(),
        ], $extra));

        $transaction->update([
            'transaction_code' => 'WTX'.str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT),
        ]);

        return $transaction->fresh();
    }

    /**
     * @throws \RuntimeException
     */
    private function assertPositive($amount): float
    {
        $amount = (float) $amount;

        if ($amount <= 0) {
            throw new \RuntimeException('Số tiền phải lớn hơn 0.'); // BR003
        }

        return $amount;
    }

    /**
     * @throws \RuntimeException
     */
    private function assertNotLocked(Wallet $wallet): void
    {
        if ($wallet->isLocked()) {
            throw new \RuntimeException('Ví đang bị khóa.'); // BR012
        }
    }
}
