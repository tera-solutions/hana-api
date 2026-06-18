<?php

namespace App\Modules\Education\Assignment\Models;

use App\Modules\Education\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentTarget extends Model
{
    protected $table = 'edu_assignment_targets';

    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
