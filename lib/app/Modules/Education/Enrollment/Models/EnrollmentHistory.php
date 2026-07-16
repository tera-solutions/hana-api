<?php

namespace App\Modules\Education\Enrollment\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class EnrollmentHistory extends Model
{
    use BelongsToBusiness;

    protected $table = 'edu_enrollment_histories';

    protected $guarded = [];

    protected $casts = [
        'effective_at' => 'datetime',
    ];
}
