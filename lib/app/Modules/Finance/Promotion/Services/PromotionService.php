<?php

namespace App\Modules\Finance\Promotion\Services;

use App\Helpers\Task;
use App\Modules\Finance\Promotion\Models\Promotion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class PromotionService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['rules', 'rewards', 'vouchers'];

    /**
     * Paginated, filterable list (promotion.md §XIII).
     */
    public function paginate(array $params = [])
    {
        $query = Promotion::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('promotion_code', 'like', "%{$search}%")
                    ->orWhere('promotion_name', 'like', "%{$search}%");
            });
        }

        foreach (['promotion_type', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['active_on'])) {
            $query->whereDate('start_date', '<=', $params['active_on'])
                ->whereDate('end_date', '>=', $params['active_on']);
        }

        $this->applySort($query, $params, ['promotion_code', 'promotion_name', 'priority', 'start_date', 'end_date', 'status', 'created_at']);

        return $query->withCount('vouchers')->paginate($this->resolvePerPage($params));
    }

    public function find($id): Promotion
    {
        return Promotion::with(self::RELATIONS)->findOrFail($id);
    }

    public function create(array $data): Promotion
    {
        return DB::transaction(function () use ($data) {
            $rules = $data['rules'] ?? [];
            $rewards = $data['rewards'] ?? [];
            unset($data['rules'], $data['rewards']);

            $promotion = new Promotion($data);
            $promotion->promotion_code = $this->generateCode();
            $promotion->status = $data['status'] ?? Promotion::STATUS_DRAFT;
            $promotion->save();

            $this->syncRules($promotion, $rules);
            $this->syncRewards($promotion, $rewards);

            return $this->find($promotion->id);
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function update($id, array $data): Promotion
    {
        return DB::transaction(function () use ($id, $data) {
            $promotion = Promotion::findOrFail($id);

            if (in_array($promotion->status, [Promotion::STATUS_EXPIRED, Promotion::STATUS_CLOSED], true)) {
                throw new \RuntimeException('Không thể chỉnh sửa chương trình đã kết thúc hoặc hết hạn.');
            }

            unset($data['id'], $data['promotion_code'], $data['status'], $data['approved_by'], $data['approved_at']);

            $rules = array_key_exists('rules', $data) ? $data['rules'] : null;
            $rewards = array_key_exists('rewards', $data) ? $data['rewards'] : null;
            unset($data['rules'], $data['rewards']);

            $promotion->fill($data)->save();

            if (is_array($rules)) {
                $this->syncRules($promotion, $rules);
            }
            if (is_array($rewards)) {
                $this->syncRewards($promotion, $rewards);
            }

            return $this->find($promotion->id);
        });
    }

    /**
     * Activate a promotion (promotion.md §XVII). Only draft/pending/paused can go live.
     *
     * @throws \RuntimeException
     */
    public function activate($id): Promotion
    {
        $promotion = Promotion::findOrFail($id);

        if (! in_array($promotion->status, [Promotion::STATUS_DRAFT, Promotion::STATUS_PENDING, Promotion::STATUS_PAUSED], true)) {
            throw new \RuntimeException('Chỉ có thể kích hoạt chương trình nháp, chờ duyệt hoặc tạm ngưng.');
        }
        if ($promotion->end_date->isPast()) {
            throw new \RuntimeException('Chương trình đã hết hạn, không thể kích hoạt.');
        }

        $promotion->update([
            'status' => Promotion::STATUS_ACTIVE,
            'approved_by' => $this->actingUserId(),
            'approved_at' => now(),
        ]);

        return $this->find($promotion->id);
    }

    /**
     * @throws \RuntimeException
     */
    public function pause($id): Promotion
    {
        $promotion = Promotion::findOrFail($id);

        if ($promotion->status !== Promotion::STATUS_ACTIVE) {
            throw new \RuntimeException('Chỉ có thể tạm ngưng chương trình đang chạy.');
        }

        $promotion->update(['status' => Promotion::STATUS_PAUSED]);

        return $this->find($promotion->id);
    }

    /**
     * @throws \RuntimeException
     */
    public function close($id): Promotion
    {
        $promotion = Promotion::findOrFail($id);

        if (in_array($promotion->status, [Promotion::STATUS_CLOSED, Promotion::STATUS_EXPIRED], true)) {
            throw new \RuntimeException('Chương trình đã kết thúc.');
        }

        $promotion->update(['status' => Promotion::STATUS_CLOSED]);

        return $this->find($promotion->id);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rules
     */
    private function syncRules(Promotion $promotion, array $rules): void
    {
        $promotion->rules()->delete();

        foreach ($rules as $rule) {
            $promotion->rules()->create([
                'rule_type' => $rule['rule_type'],
                'rule_value' => $rule['rule_value'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rewards
     */
    private function syncRewards(Promotion $promotion, array $rewards): void
    {
        $promotion->rewards()->delete();

        foreach ($rewards as $reward) {
            $promotion->rewards()->create([
                'reward_type' => $reward['reward_type'],
                'reward_value' => $reward['reward_value'] ?? null,
            ]);
        }
    }

    private function generateCode(): string
    {
        $count = Task::setAndGetReferenceCount('promotion');

        return Task::generateReferenceNumber('promotion', $count, 'PROMO');
    }

    private function actingUserId(): int|string|null
    {
        return Auth::guard('api')->id() ?? Auth::id();
    }
}
