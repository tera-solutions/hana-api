<?php

namespace App\Modules\Finance\BankAccount\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * Bank account (Tài khoản ngân hàng) belonging polymorphically to an HR entity
 * (Teacher, later Staff). One row per owner.
 */
class BankAccount extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'fin_bank_accounts';

    protected $guarded = [];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
