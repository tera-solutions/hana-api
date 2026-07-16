<?php

namespace App\Modules\System\Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class SubscriptionInvoice extends Model
{
    use BelongsToBusiness;

    protected $table = 'sys_subscription_invoices';

    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'datetime',
    ];
}
