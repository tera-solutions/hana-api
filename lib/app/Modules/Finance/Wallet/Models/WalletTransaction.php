<?php

namespace App\Modules\Finance\Wallet\Models;

use App\Modules\Finance\Wallet\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;

/**
 * An immutable ledger entry against a wallet (table `fin_wallet_transactions`).
 */
class WalletTransaction extends Model
{
    use BelongsToBusiness;

    protected $table = 'fin_wallet_transactions';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public const TYPE_DEPOSIT = WalletTransactionType::Deposit->value;

    public const TYPE_PAYMENT = WalletTransactionType::Payment->value;

    public const TYPE_REFUND = WalletTransactionType::Refund->value;

    public const TYPE_BONUS = WalletTransactionType::Bonus->value;

    public const TYPE_SALARY = WalletTransactionType::Salary->value;

    public const TYPE_ADJUSTMENT = WalletTransactionType::Adjustment->value;

    public const TYPE_EXPIRE = WalletTransactionType::Expire->value;

    public const REF_TRANSACTION = 'transaction';

    public const REF_INVOICE = 'invoice';

    public const REF_PAYMENT = 'payment';

    public const REF_PAYROLL = 'payroll';

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }
}
