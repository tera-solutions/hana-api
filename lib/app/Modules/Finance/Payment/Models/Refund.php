<?php

namespace App\Modules\Finance\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class Refund extends Model
{
    use BelongsToBusiness;

    protected $table = 'fin_refunds';

    protected $guarded = [];

    protected $casts = [
        'refunded_at' => 'datetime',
    ];
}
