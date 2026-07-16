<?php

namespace App\Modules\Finance\Invoice\Models;

use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\Education\Student\Models\Student;
use App\Modules\Finance\Invoice\Enums\InvoiceStatus;
use App\Modules\Finance\Invoice\Enums\InvoiceType;
use App\Modules\Finance\Payment\Models\Payment;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

/**
 * Financial document (table `fin_invoices`) — receivable or payable (invoice.md).
 */
class Invoice extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'fin_invoices';

    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public const TYPE_RECEIVABLE = InvoiceType::Receivable->value;

    public const TYPE_PAYABLE = InvoiceType::Payable->value;

    public const STATUS_DRAFT = InvoiceStatus::Draft->value;

    public const STATUS_PENDING = InvoiceStatus::Pending->value;

    public const STATUS_APPROVED = InvoiceStatus::Approved->value;

    public const STATUS_PENDING_PAYMENT = InvoiceStatus::PendingPayment->value;

    public const STATUS_PARTIAL = InvoiceStatus::Partial->value;

    public const STATUS_PAID = InvoiceStatus::Paid->value;

    public const STATUS_CANCELLED = InvoiceStatus::Cancelled->value;

    public const STATUS_REFUNDED = InvoiceStatus::Refunded->value;

    public const STATUS_CLOSED = InvoiceStatus::Closed->value;

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(InvoiceHistory::class, 'invoice_id');
    }

    public function isReceivable(): bool
    {
        return $this->invoice_type === self::TYPE_RECEIVABLE;
    }

    public function isPayable(): bool
    {
        return $this->invoice_type === self::TYPE_PAYABLE;
    }
}
