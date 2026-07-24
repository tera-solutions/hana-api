<?php

namespace App\Modules\Finance\BusinessBankAccount\Services;

use App\Modules\Finance\BusinessBankAccount\Models\BusinessBankAccount;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class BusinessBankAccountService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = BusinessBankAccount::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('account_holder', 'like', "%{$search}%");
            });
        }

        foreach (['status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applySort($query, $params, ['bank_name', 'is_default', 'status', 'created_at']);

        return $query->orderByDesc('is_default')->paginate($this->resolvePerPage($params));
    }

    public function find($id): BusinessBankAccount
    {
        return BusinessBankAccount::findOrFail($id);
    }

    /**
     * The account used to build invoice payment QR codes — falls back to the
     * business's oldest active account when none is marked default.
     */
    public function findDefault(int $businessId): ?BusinessBankAccount
    {
        return BusinessBankAccount::where('business_id', $businessId)
            ->where('status', BusinessBankAccount::STATUS_ACTIVE)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    public function create(array $data): BusinessBankAccount
    {
        return DB::transaction(function () use ($data) {
            $account = new BusinessBankAccount($data);
            $account->status = BusinessBankAccount::STATUS_ACTIVE;
            $account->is_default = ! empty($data['is_default']);
            $account->save();

            if ($account->is_default) {
                $this->clearOtherDefaults($account);
            }

            return $this->find($account->id);
        });
    }

    public function update($id, array $data): BusinessBankAccount
    {
        return DB::transaction(function () use ($id, $data) {
            $account = $this->find($id);

            unset($data['id'], $data['business_id'], $data['status']);

            $account->update($data);

            if ($account->is_default) {
                $this->clearOtherDefaults($account);
            }

            return $this->find($id);
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function suspend($id): BusinessBankAccount
    {
        $account = $this->find($id);

        if ($account->status === BusinessBankAccount::STATUS_INACTIVE) {
            throw new \RuntimeException('Tài khoản đang ở trạng thái ngừng.');
        }

        if ($account->is_default && $this->hasOtherActiveAccounts($account)) {
            throw new \RuntimeException('Vui lòng đặt tài khoản khác làm mặc định trước khi ngừng sử dụng tài khoản này.');
        }

        $account->update(['status' => BusinessBankAccount::STATUS_INACTIVE, 'is_default' => false]);

        return $this->find($id);
    }

    /**
     * @throws \RuntimeException
     */
    public function setDefault($id): BusinessBankAccount
    {
        $account = $this->find($id);

        if ($account->status !== BusinessBankAccount::STATUS_ACTIVE) {
            throw new \RuntimeException('Chỉ có thể đặt tài khoản đang hoạt động làm mặc định.');
        }

        DB::transaction(function () use ($account) {
            $account->update(['is_default' => true]);
            $this->clearOtherDefaults($account);
        });

        return $this->find($id);
    }

    /**
     * Static (no amount) VietQR quick-link image URL for printing/pinning at
     * the counter — see `Invoice/Services/InvoiceService::qrForBusinessAccount()`
     * for the amount-bearing variant used on an actual invoice.
     */
    public function qr($id): array
    {
        $account = $this->find($id);

        return [
            'qr_image' => sprintf(
                'https://img.vietqr.io/image/%s-%s-compact2.png',
                $account->bank_code,
                $account->account_number,
            ),
        ];
    }

    private function hasOtherActiveAccounts(BusinessBankAccount $account): bool
    {
        return BusinessBankAccount::where('business_id', $account->business_id)
            ->where('id', '<>', $account->id)
            ->where('status', BusinessBankAccount::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * @throws \RuntimeException
     */
    public function restore($id): BusinessBankAccount
    {
        $account = $this->find($id);

        if ($account->status !== BusinessBankAccount::STATUS_INACTIVE) {
            throw new \RuntimeException('Chỉ có thể khôi phục tài khoản đang ngừng.');
        }

        $account->update(['status' => BusinessBankAccount::STATUS_ACTIVE]);

        return $this->find($id);
    }

    /**
     * Exactly one default account per business — unset it on every other row.
     */
    private function clearOtherDefaults(BusinessBankAccount $account): void
    {
        BusinessBankAccount::where('business_id', $account->business_id)
            ->where('id', '<>', $account->id)
            ->update(['is_default' => false]);
    }
}
