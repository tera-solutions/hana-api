<?php

namespace App\Modules\Finance\Wallet\Models;

use App\Modules\Finance\Wallet\Enums\WalletStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A customer's internal balance (table `fin_wallets`). One wallet per owner (BR001), with
 * separate available / bonus / frozen buckets (wallet.md §V "Đề xuất Hana").
 */
class Wallet extends Model
{
    protected $table = 'fin_wallets';

    protected $guarded = [];

    protected $casts = [
        'available_balance' => 'decimal:2',
        'bonus_balance' => 'decimal:2',
        'frozen_balance' => 'decimal:2',
    ];

    public const OWNER_PARENT = 'parent';

    public const OWNER_CUSTOMER = 'customer';

    public const OWNER_TEACHER = 'teacher';

    public const STATUS_ACTIVE = WalletStatus::Active->value;

    public const STATUS_LOCKED = WalletStatus::Locked->value;

    public const STATUS_CLOSED = WalletStatus::Closed->value;

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id')->latest('id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(WalletAdjustment::class, 'wallet_id')->latest('id');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    /** Total spendable balance: bonus is spent before available (BR007). */
    public function spendableBalance(): float
    {
        return (float) $this->available_balance + (float) $this->bonus_balance;
    }
}
