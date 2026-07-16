<?php

namespace App\Modules\HR\Teacher\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;

class TeacherHistory extends Model
{
    use BelongsToBusiness;

    protected $table = 'hr_teacher_histories';

    protected $guarded = [];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
