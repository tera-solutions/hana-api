<?php

namespace App\Modules\Finance\Payment\Models;

use App\Modules\Finance\Account\Models\Account;
use App\Modules\Finance\Invoice\Models\Invoice;
use App\Modules\Finance\Payment\Enums\PaymentDirection;
use App\Modules\Finance\Payment\Enums\PaymentStatus;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * A real cash-flow transaction — receipt (IN) or disbursement (OUT) — recorded in
 * `fin_payments` (payment.md). Only a `confirmed` payment moves a fund balance (BR-03).
 */
class Payment extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'fin_payments';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public const DIRECTION_IN = PaymentDirection::In->value;

    public const DIRECTION_OUT = PaymentDirection::Out->value;

    public const STATUS_DRAFT = PaymentStatus::Draft->value;

    public const STATUS_PENDING = PaymentStatus::Pending->value;

    public const STATUS_CONFIRMED = PaymentStatus::Confirmed->value;

    public const STATUS_CANCELLED = PaymentStatus::Cancelled->value;

    public const STATUS_REVERSED = PaymentStatus::Reversed->value;

    public const STATUS_REFUNDED = PaymentStatus::Refunded->value;

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * The payment this one reverses/refunds (set on reverse & refund transactions).
     */
    public function parentPayment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_payment_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'payment_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PaymentHistory::class, 'payment_id');
    }
}
