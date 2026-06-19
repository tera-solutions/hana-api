<?php

namespace App\Modules\Finance\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eligibility condition for a promotion (table `fin_promotion_rules`).
 */
class PromotionRule extends Model
{
    protected $table = 'fin_promotion_rules';

    protected $guarded = [];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }
}
