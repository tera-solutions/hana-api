<?php

namespace App\Modules\Finance\SubscriptionPackage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPackageDiscountRule extends Model
{
    protected $table = 'fin_subscription_package_discount_rules';

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'enabled' => 'boolean',
    ];

    public const TYPE_MULTI_TERM = 'multi_term';

    public const TYPE_SIBLING = 'sibling';

    public const TYPE_CODE = 'code';

    public function package(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }
}
