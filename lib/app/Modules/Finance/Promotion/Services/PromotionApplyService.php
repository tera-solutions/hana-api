<?php

namespace App\Modules\Finance\Promotion\Services;

use App\Modules\Finance\Promotion\Models\Promotion;
use App\Modules\Finance\Promotion\Models\PromotionUsage;
use App\Modules\Finance\Promotion\Models\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * The promotion engine: resolves the applicable promotion, computes the discount and
 * records the usage (promotion.md §X). It does not mutate invoices or wallets — it
 * returns the computed amounts for the billing pipeline to consume.
 */
class PromotionApplyService
{
    public function __construct(private VoucherService $voucherService) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{promotion: Promotion, voucher: ?Voucher, original_amount: float, discount_amount: float, final_amount: float, usage: PromotionUsage}
     *
     * @throws \RuntimeException
     */
    public function apply(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new \RuntimeException('Giá trị đơn hàng phải lớn hơn 0.');
            }

            [$promotion, $voucher] = $this->resolve($data);

            $this->assertRulesSatisfied($promotion, $amount);

            $discount = $this->computeDiscount($promotion, $amount);
            $final = round($amount - $discount, 2);

            $usage = PromotionUsage::create([
                'promotion_id' => $promotion->id,
                'voucher_id' => $voucher?->id,
                'enrollment_id' => $data['enrollment_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'discount_amount' => $discount,
                'used_at' => now(),
            ]);

            if ($voucher) {
                $voucher->increment('used_count');
                if (! $voucher->fresh()->hasRemainingUses()) {
                    $voucher->update(['status' => Voucher::STATUS_USED]);
                }
            }

            return [
                'promotion' => $promotion,
                'voucher' => $voucher,
                'original_amount' => $amount,
                'discount_amount' => $discount,
                'final_amount' => $final,
                'usage' => $usage,
            ];
        });
    }

    /**
     * @return array{0: Promotion, 1: ?Voucher}
     *
     * @throws \RuntimeException
     */
    private function resolve(array $data): array
    {
        if (! empty($data['voucher_code'])) {
            $voucher = $this->voucherService->validateCode($data['voucher_code']);

            return [$voucher->promotion, $voucher];
        }

        if (! empty($data['promotion_id'])) {
            $promotion = Promotion::findOrFail($data['promotion_id']);
            $this->voucherService->assertPromotionRunning($promotion);

            return [$promotion, null];
        }

        throw new \RuntimeException('Cần cung cấp mã voucher hoặc chương trình khuyến mãi.');
    }

    /**
     * Compute the discount, capped so the invoice can never go negative (BR009) and the
     * discount never exceeds the fee (BR010).
     */
    private function computeDiscount(Promotion $promotion, float $amount): float
    {
        $raw = match ($promotion->discount_type) {
            Promotion::DISCOUNT_PERCENT => $amount * ((float) $promotion->discount_value) / 100,
            Promotion::DISCOUNT_FIXED => (float) $promotion->discount_value,
            default => 0.0,
        };

        if ($promotion->max_discount !== null) {
            $raw = min($raw, (float) $promotion->max_discount);
        }

        return round(min($raw, $amount), 2);
    }

    /**
     * Enforce the minimum-order rule when present (promotion.md §VIII BR001).
     *
     * @throws \RuntimeException
     */
    private function assertRulesSatisfied(Promotion $promotion, float $amount): void
    {
        $minOrder = $promotion->rules()->where('rule_type', 'min_order')->value('rule_value');

        if ($minOrder !== null && $amount < (float) $minOrder) {
            throw new \RuntimeException('Đơn hàng chưa đạt giá trị tối thiểu để áp dụng khuyến mãi.');
        }
    }
}
