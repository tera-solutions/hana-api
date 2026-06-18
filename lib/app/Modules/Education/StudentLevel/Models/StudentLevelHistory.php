<?php

namespace App\Modules\Education\StudentLevel\Models;

use App\Modules\Education\Level\Models\Level;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLevelHistory extends Model
{
    protected $table = 'edu_student_level_histories';

    protected $guarded = [];

    protected $casts = [
        'score' => 'decimal:2',
        'effective_at' => 'datetime',
    ];

    public function studentLevel(): BelongsTo
    {
        return $this->belongsTo(StudentLevel::class, 'student_level_id');
    }

    public function fromLevel(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'from_level_id');
    }

    public function toLevel(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'to_level_id');
    }
}
