<?php

namespace App\Modules\Education\Lesson\Models;

use App\Modules\Education\Lesson\Enums\LessonActivityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;
use Package\Database\Concerns\HasAvatarUrl;

class LessonActivity extends Model
{
    use HasAuditFields;
    use HasAvatarUrl;

    protected $table = 'edu_lesson_activities';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
        'duration' => 'integer',
    ];

    protected $appends = ['avatar_url'];

    public const STATUS_PENDING = LessonActivityStatus::Pending->value;

    public const STATUS_IN_PROGRESS = LessonActivityStatus::InProgress->value;

    public const STATUS_COMPLETED = LessonActivityStatus::Completed->value;

    /**
     * edu_lesson_activities has no deleted_at column, mirroring the parent lesson.
     *
     * @return string[]
     */
    public function getAuditColumns(): array
    {
        return ['created_by', 'updated_by'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }
}
