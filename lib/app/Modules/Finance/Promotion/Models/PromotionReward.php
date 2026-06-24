<?php

namespace App\Modules\Finance\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reward line produced by a promotion (table `fin_promotion_rewards`).
 */
class PromotionReward extends Model
{
    protected $table = 'fin_promotion_rewards';

    protected $guarded = [];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }
}
