<?php

namespace App\Modules\Education\Lesson\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Lesson\Enums\LessonStatus;
use App\Modules\Education\LessonPlan\Models\LessonPlan;
use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLesson;
use App\Modules\Education\Room\Models\Room;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Package\Database\Concerns\HasAuditFields;
use Package\Database\Concerns\HasAvatarUrl;

class Lesson extends Model
{
    use HasAuditFields;
    use HasAvatarUrl;
    use LogsActivity;

    protected $table = 'edu_lessons';

    protected $guarded = [];

    protected $casts = [
        'lesson_no' => 'integer',
        'lesson_date' => 'date',
        'completed_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    protected $appends = ['avatar_url'];

    public const STATUS_SCHEDULED = LessonStatus::Scheduled->value;

    public const STATUS_CONFIRMED = LessonStatus::Confirmed->value;

    public const STATUS_IN_PROGRESS = LessonStatus::InProgress->value;

    public const STATUS_COMPLETED = LessonStatus::Completed->value;

    public const STATUS_CANCELLED = LessonStatus::Cancelled->value;

    public const STATUS_LOCKED = LessonStatus::Locked->value;

    /**
     * edu_lessons has no deleted_at column (lesson.md §16); cancellation is a status.
     *
     * @return string[]
     */
    public function getAuditColumns(): array
    {
        return ['created_by', 'updated_by'];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function lessonPlan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class, 'lesson_plan_id');
    }

    public function lessonPlanLesson(): BelongsTo
    {
        return $this->belongsTo(LessonPlanLesson::class, 'lesson_plan_lesson_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(LessonHistory::class, 'lesson_id')->latest('id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LessonActivity::class, 'lesson_id')->orderBy('sort_order');
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
