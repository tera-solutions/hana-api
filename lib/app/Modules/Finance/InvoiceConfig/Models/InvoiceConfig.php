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
        'late_fee_enabled' => 'boolean',
        'late_fee_percent' => 'decimal:2',
        'reminder_before_due_days' => 'integer',
        'reminder_on_overdue' => 'boolean',
        'reminder_channels' => 'array',
    ];

    // Which Student::STATUS_* a student is moved to when they have an
    // overdue, unpaid receivable invoice — consumed by
    // InvoiceService::syncStudentDebtStatus(). Must be one of Student's own
    // status values (not InvoiceConfig-local strings) since that's what
    // actually gets written to edu_students.status.
    public const STUDENT_STATUS_DEBT = 'debt';

    public const STUDENT_STATUS_SUSPENDED = 'suspended';

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
