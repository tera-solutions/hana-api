<?php

namespace App\Modules\Finance\Promotion\Models;

use App\Modules\CRM\Parent\Models\ParentModel;
use App\Modules\Finance\Promotion\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

/**
 * A parent-to-parent referral and its reward state (table `fin_referrals`).
 */
class Referral extends Model
{
    use HasAuditFields;

    protected $table = 'fin_referrals';

    protected $guarded = [];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'rewarded_at' => 'datetime',
    ];

    public const STATUS_PENDING = ReferralStatus::Pending->value;

    public const STATUS_REWARDED = ReferralStatus::Rewarded->value;

    public const STATUS_CANCELLED = ReferralStatus::Cancelled->value;

    /**
     * @return string[]
     */
    public function getAuditColumns(): array
    {
        return ['created_by', 'updated_by'];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'referrer_parent_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'referred_parent_id');
    }
}
