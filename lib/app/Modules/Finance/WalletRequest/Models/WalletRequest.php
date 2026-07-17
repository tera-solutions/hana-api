<?php

namespace App\Modules\Finance\WalletRequest\Models;

use App\Modules\Finance\BankAccount\Models\BankAccount;
use App\Modules\Finance\Wallet\Models\Wallet;
use App\Modules\Finance\WalletRequest\Enums\WalletRequestStatus;
use App\Modules\Finance\WalletRequest\Enums\WalletRequestType;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

/**
 * A teacher's request to deposit into or withdraw from their own wallet (table
 * `fin_wallet_requests`). No payment gateway: an admin reviews the request,
 * moves the money outside the system (bank transfer), then marks it complete —
 * only then does the wallet ledger change (via `WalletService::deposit/payment`).
 */
class WalletRequest extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'fin_wallet_requests';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const TYPE_DEPOSIT = WalletRequestType::Deposit->value;

    public const TYPE_WITHDRAW = WalletRequestType::Withdraw->value;

    public const STATUS_PENDING = WalletRequestStatus::Pending->value;

    public const STATUS_APPROVED = WalletRequestStatus::Approved->value;

    public const STATUS_REJECTED = WalletRequestStatus::Rejected->value;

    public const STATUS_COMPLETED = WalletRequestStatus::Completed->value;

    public const STATUS_CANCELLED = WalletRequestStatus::Cancelled->value;

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function isWithdraw(): bool
    {
        return $this->request_type === self::TYPE_WITHDRAW;
    }
}
