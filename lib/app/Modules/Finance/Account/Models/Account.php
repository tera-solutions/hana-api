<?php

namespace App\Modules\Finance\Account\Models;

use App\Modules\Finance\Account\Enums\AccountType;
use App\Modules\Finance\Payment\Models\Payment;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * A fund / quỹ (cash, bank or e-wallet) whose balance is moved by confirmed
 * payments (table `fin_accounts`, payment.md §VI).
 */
class Account extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'fin_accounts';

    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public const TYPE_CASH = AccountType::Cash->value;

    public const TYPE_BANK = AccountType::Bank->value;

    public const TYPE_EWALLET = AccountType::Ewallet->value;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'account_id');
    }
}
