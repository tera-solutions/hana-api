<?php

namespace App\Modules\Finance\Debt\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class Debt extends Model
{
    use BelongsToBusiness;

    protected $table = 'fin_debts';

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
    ];
}
