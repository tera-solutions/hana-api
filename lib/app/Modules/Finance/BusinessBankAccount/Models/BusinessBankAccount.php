<?php

namespace App\Modules\Finance\BusinessBankAccount\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

/**
 * The business's own bank account for RECEIVING tuition payments (table
 * `fin_business_bank_accounts`) — distinct from `fin_bank_accounts`, which
 * holds teacher/staff payout accounts. `bank_code` is the VietQR bank
 * identifier (BIN or acronym) used to build payment QR codes.
 */
class BusinessBankAccount extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'fin_business_bank_accounts';

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
