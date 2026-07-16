<?php

namespace App\Modules\Education\Grade\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class Grade extends Model
{
    use BelongsToBusiness;

    protected $table = 'edu_grades';

    protected $guarded = [];
}
