<?php

namespace App\Modules\Finance\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status-transition audit row for a payment (table `fin_payment_histories`).
 */
class PaymentHistory extends Model
{
    protected $table = 'fin_payment_histories';

    protected $guarded = [];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
