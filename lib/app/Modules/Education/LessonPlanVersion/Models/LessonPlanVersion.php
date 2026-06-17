<?php

namespace App\Modules\Education\LessonPlanVersion\Models;

use App\Modules\Education\LessonPlan\Models\LessonPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPlanVersion extends Model
{
    protected $table = 'edu_lesson_plan_versions';

    protected $guarded = [];

    protected $casts = [
        'version' => 'integer',
        'published_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class, 'lesson_plan_id');
    }
}
