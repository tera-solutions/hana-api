<?php

namespace App\Modules\Education\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseHistory extends Model
{
    protected $table = 'edu_course_histories';

    protected $guarded = [];

    protected $casts = [
        'from_active' => 'boolean',
        'to_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
