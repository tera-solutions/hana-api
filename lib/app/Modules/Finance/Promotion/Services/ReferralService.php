<?php

namespace App\Modules\Finance\Promotion\Services;

use App\Modules\Finance\Promotion\Models\Referral;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * Referral programme (promotion.md §XI). The reward is recorded here; crediting it to a
 * wallet is left to the wallet module (not yet present).
 */
class ReferralService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = Referral::query();

        foreach (['referrer_parent_id', 'referred_parent_id', 'promotion_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applySort($query, $params, ['status', 'reward_amount', 'created_at']);

        return $query->with(['promotion'])->paginate($this->resolvePerPage($params));
    }

    public function create(array $data): Referral
    {
        $referral = new Referral($data);
        $referral->status = Referral::STATUS_PENDING;
        $referral->save();

        return $referral->fresh(['promotion']);
    }

    /**
     * Mark a referral as rewarded once the referred enrollment is paid (BR011).
     *
     * @throws \RuntimeException
     */
    public function reward($id, array $data): Referral
    {
        return DB::transaction(function () use ($id, $data) {
            $referral = Referral::findOrFail($id);

            if ($referral->status !== Referral::STATUS_PENDING) {
                throw new \RuntimeException('Chỉ có thể thưởng cho lượt giới thiệu đang chờ.');
            }

            $referral->update([
                'reward_amount' => $data['reward_amount'] ?? $referral->reward_amount,
                'status' => Referral::STATUS_REWARDED,
                'rewarded_at' => now(),
            ]);

            return $referral->fresh(['promotion']);
        });
    }
}
