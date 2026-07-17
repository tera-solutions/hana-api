<?php

namespace App\Modules\Finance\WalletRequest\Services;

use App\Helpers\Task;
use App\Modules\Finance\Wallet\Models\Wallet;
use App\Modules\Finance\Wallet\Services\WalletService;
use App\Modules\Finance\WalletRequest\Models\WalletRequest;
use App\Modules\HR\Teacher\Models\Teacher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * No payment gateway: a teacher requests a deposit/withdraw against their own
 * wallet, an admin approves it, moves the money outside the system (bank
 * transfer), then marks it complete — only `complete()` touches the wallet
 * ledger, via the existing `WalletService::deposit()/payment()`.
 */
class WalletRequestService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['wallet', 'bankAccount'];

    public function paginate(array $params = [])
    {
        $query = WalletRequest::query();

        foreach (['wallet_id', 'business_id', 'request_type', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['search'])) {
            $query->where('code', 'like', "%{$params['search']}%");
        }

        if (! empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        $this->applySort($query, $params, ['code', 'amount', 'status', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    public function find($id): WalletRequest
    {
        return WalletRequest::with(self::RELATIONS)->findOrFail($id);
    }

    /**
     * Resolves (or lazily creates, BR001-style) the wallet for a teacher's own
     * user id — mirrors `useTeacherWallet` on the FE: `owner_id` is the `users`
     * table id, not `hr_teachers`.
     */
    public function walletForUser(int $businessId, int $userId): Wallet
    {
        return app(WalletService::class)->createForOwner($businessId, Wallet::OWNER_TEACHER, $userId);
    }

    /**
     * `business_id`/`user_id` come from the acting token (controller), not the
     * request body — a teacher can only ever request against their OWN wallet.
     *
     * @throws \RuntimeException
     */
    public function create(array $data): WalletRequest
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new \RuntimeException('Số tiền phải lớn hơn 0.');
        }

        // BR: a teacher must have their HR profile bank account set up before using the
        // wallet at all (deposit or withdraw) — not just as a withdraw payout target.
        $bankAccountId = $this->resolveBankAccountId((int) $data['business_id'], (int) $data['user_id']);

        $wallet = $this->walletForUser((int) $data['business_id'], (int) $data['user_id']);

        $request = WalletRequest::create([
            'business_id' => $data['business_id'],
            'wallet_id' => $wallet->id,
            'code' => $this->generateCode(),
            'request_type' => $data['request_type'],
            'amount' => $amount,
            'status' => WalletRequest::STATUS_PENDING,
            'note' => $data['note'] ?? null,
            'bank_account_id' => $bankAccountId,
        ]);

        return $this->find($request->id);
    }

    /**
     * @throws \RuntimeException
     */
    public function approve(int $id): WalletRequest
    {
        $request = $this->find($id);

        if ($request->status !== WalletRequest::STATUS_PENDING) {
            throw new \RuntimeException('Chỉ có thể duyệt yêu cầu đang chờ duyệt.');
        }

        $request->update([
            'status' => WalletRequest::STATUS_APPROVED,
            'approved_by' => $this->actingUserId(),
            'approved_at' => now(),
        ]);

        return $this->find($id);
    }

    /**
     * @throws \RuntimeException
     */
    public function reject(int $id, array $data): WalletRequest
    {
        $request = $this->find($id);

        if ($request->status !== WalletRequest::STATUS_PENDING) {
            throw new \RuntimeException('Chỉ có thể từ chối yêu cầu đang chờ duyệt.');
        }

        $request->update([
            'status' => WalletRequest::STATUS_REJECTED,
            'reject_reason' => $data['reject_reason'],
        ]);

        return $this->find($id);
    }

    /**
     * @throws \RuntimeException
     */
    public function cancel(int $id): WalletRequest
    {
        $request = $this->find($id);

        if ($request->status !== WalletRequest::STATUS_PENDING) {
            throw new \RuntimeException('Chỉ có thể hủy yêu cầu đang chờ duyệt.');
        }

        $request->update(['status' => WalletRequest::STATUS_CANCELLED]);

        return $this->find($id);
    }

    /**
     * Admin confirms the money has actually moved outside the system — the
     * only step that writes the wallet ledger.
     *
     * @throws \RuntimeException
     */
    public function complete(int $id, array $data = []): WalletRequest
    {
        return DB::transaction(function () use ($id, $data) {
            $request = WalletRequest::lockForUpdate()->findOrFail($id);

            if ($request->status !== WalletRequest::STATUS_APPROVED) {
                throw new \RuntimeException('Chỉ có thể hoàn tất yêu cầu đã được duyệt.');
            }

            $walletData = [
                'wallet_id' => $request->wallet_id,
                'amount' => (float) $request->amount,
                'note' => $data['note'] ?? "Hoàn tất yêu cầu {$request->code}",
            ];

            $transaction = $request->request_type === WalletRequest::TYPE_DEPOSIT
                ? app(WalletService::class)->deposit($walletData)
                : app(WalletService::class)->payment($walletData);

            $request->update([
                'status' => WalletRequest::STATUS_COMPLETED,
                'completed_by' => $this->actingUserId(),
                'completed_at' => now(),
                'wallet_transaction_id' => $transaction->id,
            ]);

            return $this->find($id);
        });
    }

    /**
     * A wallet request (deposit or withdraw) requires the teacher to already have a
     * saved HR profile bank account — for withdraw it's the payout target, for deposit
     * it's a KYC-style gate before touching the wallet at all.
     *
     * @throws \RuntimeException
     */
    private function resolveBankAccountId(int $businessId, int $userId): int
    {
        $teacher = Teacher::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->with('bankAccount')
            ->first();

        if (! $teacher || ! $teacher->bankAccount) {
            throw new \RuntimeException('Vui lòng cập nhật thông tin tài khoản ngân hàng trong hồ sơ giáo viên trước khi thực hiện giao dịch ví.');
        }

        return $teacher->bankAccount->id;
    }

    private function generateCode(): string
    {
        $count = Task::setAndGetReferenceCount('wallet_request');

        return Task::generateReferenceNumber('wallet_request', $count, 'WR');
    }

    private function actingUserId(): int|string|null
    {
        return Auth::guard('api')->id() ?? Auth::id();
    }
}
