<?php

namespace App\Modules\Education\Lesson\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'edu_lesson_histories';

    protected $guarded = [];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }
}
