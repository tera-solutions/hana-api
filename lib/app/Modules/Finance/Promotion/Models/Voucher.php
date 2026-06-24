<?php

namespace App\Modules\Finance\Promotion\Models;

use App\Modules\Finance\Promotion\Enums\VoucherStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

/**
 * A discount code issued by a promotion (table `fin_vouchers`).
 */
class Voucher extends Model
{
    use HasAuditFields;

    protected $table = 'fin_vouchers';

    protected $guarded = [];

    protected $casts = [
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'expired_at' => 'datetime',
    ];

    public const STATUS_ACTIVE = VoucherStatus::Active->value;

    public const STATUS_USED = VoucherStatus::Used->value;

    public const STATUS_EXPIRED = VoucherStatus::Expired->value;

    public const STATUS_LOCKED = VoucherStatus::Locked->value;

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

    public function hasRemainingUses(): bool
    {
        return $this->used_count < $this->usage_limit;
    }
}
