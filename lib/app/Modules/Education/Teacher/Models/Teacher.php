<?php

namespace App\Modules\Education\Teacher\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $table = 'hr_teachers';

    protected $guarded = [];
}
