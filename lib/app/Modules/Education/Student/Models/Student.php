<?php

namespace App\Modules\Education\Student\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $table = 'edu_students';

    protected $guarded = [];
}