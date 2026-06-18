<?php

namespace App\Modules\Education\LessonPlanLesson\Models;

use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\LessonPlan\Models\LessonPlan;
use App\Modules\Education\LessonPlanMaterial\Models\LessonPlanMaterial;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class LessonPlanLesson extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_lesson_plan_lessons';

    protected $guarded = [];

    protected $casts = [
        'lesson_no' => 'integer',
        'duration' => 'integer',
    ];

    /**
     * Content fields snapshotted onto a generated session (BR008).
     */
    public const SNAPSHOT_FIELDS = [
        'lesson_no', 'lesson_title', 'objective', 'vocabulary',
        'grammar', 'activities', 'homework', 'duration',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class, 'lesson_plan_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LessonPlanMaterial::class, 'lesson_plan_lesson_id');
    }

    /**
     * Actual class lessons (edu_lessons) generated from this template (lesson.md §16).
     */
    public function generatedLessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'lesson_plan_lesson_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return collect(self::SNAPSHOT_FIELDS)
            ->mapWithKeys(fn ($field) => [$field => $this->{$field}])
            ->all();
    }
}
