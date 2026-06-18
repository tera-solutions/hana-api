<?php

namespace App\Modules\Education\StudentLevel\Models;

use App\Modules\Education\Level\Models\Level;
use App\Modules\Education\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLevelAssessment extends Model
{
    protected $table = 'edu_student_level_assessments';

    protected $guarded = [];

    protected $casts = [
        'score' => 'decimal:2',
        'assessed_at' => 'datetime',
    ];

    public const TYPE_PLACEMENT_TEST = 'placement_test';

    public const TYPE_TEACHER_EVALUATION = 'teacher_evaluation';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }
}
