<?php

namespace App\Modules\Finance\Debt\Models;

use App\Modules\Finance\Debt\Enums\AdjustmentType;
use App\Modules\Finance\Invoice\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A correction, discount or write-off recorded against an invoice's debt
 * (table `fin_debt_adjustments`, debt.md §XI).
 */
class DebtAdjustment extends Model
{
    protected $table = 'fin_debt_adjustments';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public const TYPE_CORRECTION = AdjustmentType::Correction->value;

    public const TYPE_DISCOUNT = AdjustmentType::Discount->value;

    public const TYPE_WRITE_OFF = AdjustmentType::WriteOff->value;

    public const STATUS_APPLIED = 'applied';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
