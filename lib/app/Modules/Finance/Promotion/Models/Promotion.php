<?php

namespace App\Modules\Finance\Promotion\Models;

use App\Modules\Finance\Promotion\Enums\DiscountType;
use App\Modules\Finance\Promotion\Enums\PromotionStatus;
use App\Modules\Finance\Promotion\Enums\PromotionType;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * A promotion programme (table `fin_promotions`).
 */
class Promotion extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'fin_promotions';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'priority' => 'integer',
        'discount_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'bonus_lesson' => 'integer',
        'bonus_wallet_amount' => 'decimal:2',
    ];

    public const STATUS_DRAFT = PromotionStatus::Draft->value;

    public const STATUS_PENDING = PromotionStatus::Pending->value;

    public const STATUS_ACTIVE = PromotionStatus::Active->value;

    public const STATUS_PAUSED = PromotionStatus::Paused->value;

    public const STATUS_EXPIRED = PromotionStatus::Expired->value;

    public const STATUS_CLOSED = PromotionStatus::Closed->value;

    public const DISCOUNT_PERCENT = DiscountType::Percent->value;

    public const DISCOUNT_FIXED = DiscountType::Fixed->value;

    public const TYPE_VOUCHER = PromotionType::Voucher->value;

    public function rules(): HasMany
    {
        return $this->hasMany(PromotionRule::class, 'promotion_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(PromotionReward::class, 'promotion_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'promotion_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class, 'promotion_id');
    }

    /**
     * Whether the programme is live for the given date.
     */
    public function isRunningOn(\DateTimeInterface $date): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->start_date->lte($date)
            && $this->end_date->gte($date);
    }
}
