<?php

namespace App\Modules\Finance\Invoice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status-transition audit row for an invoice (table `fin_invoice_histories`).
 */
class InvoiceHistory extends Model
{
    protected $table = 'fin_invoice_histories';

    protected $guarded = [];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
