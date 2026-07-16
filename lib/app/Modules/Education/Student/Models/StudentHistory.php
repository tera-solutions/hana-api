<?php

namespace App\Modules\Education\Student\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;

class StudentHistory extends Model
{
    use BelongsToBusiness;

    protected $table = 'edu_student_histories';

    protected $guarded = [];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
