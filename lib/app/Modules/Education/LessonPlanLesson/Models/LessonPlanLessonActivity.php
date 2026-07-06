<?php

namespace App\Modules\Education\LessonPlanLesson\Models;

use App\Modules\Education\Lesson\Enums\LessonActivityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;
use Package\Database\Concerns\HasAvatarUrl;

class LessonPlanLessonActivity extends Model
{
    use HasAuditFields;
    use HasAvatarUrl;
    use SoftDeletes;

    protected $table = 'edu_lesson_plan_lesson_activities';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
        'duration' => 'integer',
    ];

    protected $appends = ['avatar_url'];

    public const STATUS_PENDING = LessonActivityStatus::Pending->value;

    public const STATUS_IN_PROGRESS = LessonActivityStatus::InProgress->value;

    public const STATUS_COMPLETED = LessonActivityStatus::Completed->value;

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(LessonPlanLesson::class, 'lesson_plan_lesson_id');
    }
}
