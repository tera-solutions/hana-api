<?php

namespace App\Modules\Finance\Account\Services;

use App\Helpers\Task;
use App\Modules\Finance\Account\Models\Account;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class AccountService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['business', 'branch'];

    public function paginate(array $params = [])
    {
        $query = Account::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        foreach (['business_id', 'branch_id', 'type', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applySort($query, $params, ['code', 'name', 'type', 'balance', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    public function find($id): Account
    {
        return Account::with(self::RELATIONS)->findOrFail($id);
    }

    public function create(array $data): Account
    {
        return DB::transaction(function () use ($data) {
            $account = new Account($data);
            $account->code = $this->generateCode($account->business_id);
            $account->balance = (float) ($data['balance'] ?? 0);
            $account->status = $data['status'] ?? Account::STATUS_ACTIVE;
            $account->save();

            return $this->find($account->id);
        });
    }

    public function update($id, array $data): Account
    {
        $account = $this->find($id);

        // Identity and the running balance are immutable here (balance moves only
        // through confirmed payments — payment.md BR-03).
        unset($data['id'], $data['code'], $data['business_id'], $data['balance']);

        $account->update($data);

        return $this->find($id);
    }

    /**
     * @throws \RuntimeException
     */
    public function suspend($id): Account
    {
        $account = $this->find($id);

        if ($account->status === Account::STATUS_INACTIVE) {
            throw new \RuntimeException('Quỹ đang ở trạng thái ngừng.');
        }

        $account->update(['status' => Account::STATUS_INACTIVE]);

        return $this->find($id);
    }

    /**
     * @throws \RuntimeException
     */
    public function restore($id): Account
    {
        $account = $this->find($id);

        if ($account->status !== Account::STATUS_INACTIVE) {
            throw new \RuntimeException('Chỉ có thể khôi phục quỹ đang ngừng.');
        }

        $account->update(['status' => Account::STATUS_ACTIVE]);

        return $this->find($id);
    }

    private function generateCode($businessId): string
    {
        $count = Task::setAndGetReferenceCount('fin_account', $businessId ?? 0);

        return Task::generateReferenceNumber('fin_account', $count, 'ACC');
    }
}
