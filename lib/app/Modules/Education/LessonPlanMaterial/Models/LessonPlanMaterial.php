<?php

namespace App\Modules\Education\LessonPlanMaterial\Models;

use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLesson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

class LessonPlanMaterial extends Model
{
    use HasAuditFields;

    protected $table = 'edu_lesson_plan_materials';

    protected $guarded = [];

    protected $casts = [
        'file_id' => 'integer',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LessonPlanLesson::class, 'lesson_plan_lesson_id');
    }
}
