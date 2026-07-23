<?php

namespace App\Modules\Finance\InvoiceConfig\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

/**
 * Per-business recurring-invoice settings (table `fin_invoice_configs`,
 * one row per business) — read by `invoices:generate-recurring`.
 */
class InvoiceConfig extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'fin_invoice_configs';

    protected $guarded = [];

    protected $casts = [
        'auto_generate' => 'boolean',
        'billing_day' => 'integer',
        'due_days' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
