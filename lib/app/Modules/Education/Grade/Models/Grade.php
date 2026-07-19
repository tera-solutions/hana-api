<?php

namespace App\Modules\Education\Grade\Models;

use App\Modules\Education\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;

class Grade extends Model
{
    use BelongsToBusiness;

    protected $table = 'edu_grades';

    protected $guarded = [];

    protected $casts = [
        'score' => 'decimal:2',
        'breakdown' => 'array',
        'finalized_at' => 'datetime',
    ];

    public const TYPE_FINAL = 'final';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
