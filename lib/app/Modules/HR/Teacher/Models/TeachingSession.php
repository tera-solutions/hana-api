<?php

namespace App\Modules\HR\Teacher\Models;

use Illuminate\Database\Eloquent\Model;
use Package\Database\Concerns\BelongsToBusiness;

class TeachingSession extends Model
{
    use BelongsToBusiness;

    protected $table = 'hr_teaching_sessions';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];
}
