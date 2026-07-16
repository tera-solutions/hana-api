<?php

namespace App\Modules\HR\Teacher\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class Contract extends Model
{
    use BelongsToBusiness;

    protected $table = 'hr_contracts';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
