<?php

namespace App\Modules\Finance\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class PaymentLog extends Model
{
    use BelongsToBusiness;

    protected $table = 'fin_payment_logs';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
