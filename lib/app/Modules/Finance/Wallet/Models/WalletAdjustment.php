<?php

namespace App\Modules\Finance\Wallet\Models;

use App\Modules\Finance\Wallet\Enums\WalletAdjustmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A manual balance adjustment with a mandatory reason and approver (table
 * `fin_wallet_adjustments`, wallet.md §XI / §XVI).
 */
class WalletAdjustment extends Model
{
    protected $table = 'fin_wallet_adjustments';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const TYPE_INCREASE = WalletAdjustmentType::Increase->value;

    public const TYPE_DECREASE = WalletAdjustmentType::Decrease->value;

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }
}
