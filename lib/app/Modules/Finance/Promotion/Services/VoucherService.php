<?php

namespace App\Modules\Finance\Promotion\Services;

use App\Modules\Finance\Promotion\Models\Promotion;
use App\Modules\Finance\Promotion\Models\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    /**
     * Generate a batch of vouchers for a promotion (promotion.md §IX "Sinh voucher").
     *
     * @return Collection<int, Voucher>
     *
     * @throws \RuntimeException
     */
    public function generate($promotionId, array $data): Collection
    {
        return DB::transaction(function () use ($promotionId, $data) {
            $promotion = Promotion::findOrFail($promotionId);

            $quantity = max(1, (int) ($data['quantity'] ?? 1));
            $usageLimit = max(1, (int) ($data['usage_limit'] ?? 1));
            $expiredAt = $data['expired_at'] ?? $promotion->end_date?->endOfDay();
            $prefix = strtoupper($data['prefix'] ?? 'V');

            $ids = [];
            for ($i = 0; $i < $quantity; $i++) {
                $voucher = Voucher::create([
                    'promotion_id' => $promotion->id,
                    'voucher_code' => $this->uniqueCode($prefix),
                    'usage_limit' => $usageLimit,
                    'used_count' => 0,
                    'expired_at' => $expiredAt,
                    'status' => Voucher::STATUS_ACTIVE,
                ]);
                $ids[] = $voucher->id;
            }

            return Voucher::whereIn('id', $ids)->get();
        });
    }

    /**
     * Resolve and validate a voucher code for use (promotion.md §IX BR006/BR007).
     *
     * @throws \RuntimeException
     */
    public function validateCode(string $code): Voucher
    {
        $voucher = Voucher::with('promotion')->where('voucher_code', $code)->first();

        if (! $voucher) {
            throw new \RuntimeException('Mã voucher không tồn tại.');
        }
        if ($voucher->status === Voucher::STATUS_LOCKED) {
            throw new \RuntimeException('Voucher đã bị khóa.');
        }
        if ($voucher->status !== Voucher::STATUS_ACTIVE || ! $voucher->hasRemainingUses()) {
            throw new \RuntimeException('Voucher đã được sử dụng hết.'); // BR007
        }
        if ($voucher->expired_at && $voucher->expired_at->isPast()) {
            throw new \RuntimeException('Voucher đã hết hạn.'); // BR006
        }

        $this->assertPromotionRunning($voucher->promotion);

        return $voucher;
    }

    /**
     * @throws \RuntimeException
     */
    public function assertPromotionRunning(?Promotion $promotion): void
    {
        if (! $promotion) {
            throw new \RuntimeException('Chương trình khuyến mãi không tồn tại.');
        }
        if ($promotion->status !== Promotion::STATUS_ACTIVE) {
            throw new \RuntimeException('Chương trình khuyến mãi chưa được kích hoạt.');
        }
        if ($promotion->start_date->isFuture() || $promotion->end_date->endOfDay()->isPast()) {
            throw new \RuntimeException('Ngoài thời gian áp dụng khuyến mãi.'); // BR005
        }
    }

    private function uniqueCode(string $prefix): string
    {
        do {
            $code = $prefix.strtoupper(Str::random(8));
        } while (Voucher::where('voucher_code', $code)->exists());

        return $code;
    }
}
