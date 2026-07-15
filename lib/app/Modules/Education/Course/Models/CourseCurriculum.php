<?php

namespace App\Modules\Education\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCurriculum extends Model
{
    protected $table = 'edu_course_curriculums';

    protected $guarded = [];

    protected $casts = [
        'order' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
