<?php

namespace App\Modules\Finance\Payment\Models;

use App\Modules\Finance\Invoice\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Allocation of a payment to a single invoice (table `fin_payment_allocations`).
 */
class PaymentAllocation extends Model
{
    protected $table = 'fin_payment_allocations';

    protected $guarded = [];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
