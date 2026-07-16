<?php

namespace App\Modules\HR\Teacher\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class Payroll extends Model
{
    use BelongsToBusiness;

    protected $table = 'hr_payrolls';

    protected $guarded = [];
}
