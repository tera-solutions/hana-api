<?php

namespace App\Modules\Education\LessonPlan\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\LessonPlan\Enums\LessonPlanStatus;
use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLesson;
use App\Modules\Education\LessonPlanVersion\Models\LessonPlanVersion;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;
use Package\Database\Concerns\HasAvatarUrl;

class LessonPlan extends Model
{
    use HasAuditFields;
    use HasAvatarUrl;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_lesson_plans';

    protected $guarded = [];

    protected $casts = [
        'version' => 'integer',
        'total_lessons' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $appends = ['avatar_url'];

    public const STATUS_DRAFT = LessonPlanStatus::Draft->value;

    public const STATUS_REVIEWING = LessonPlanStatus::Reviewing->value;

    public const STATUS_PUBLISHED = LessonPlanStatus::Published->value;

    public const STATUS_ARCHIVED = LessonPlanStatus::Archived->value;

    /**
     * Tables that reference this plan (lesson-plan.md §17). Used to enforce BR004
     * (cannot edit a plan already used by a class).
     *
     * @var array<string, string> table => lesson plan foreign key column
     */
    public const LINKED_TABLES = [
        'edu_classes' => 'lesson_plan_id',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(LessonPlanLesson::class, 'lesson_plan_id')->orderBy('lesson_no');
    }

    /**
     * Actual class lessons (edu_lessons) generated from this plan (lesson.md §16),
     * distinct from the template lessons() above.
     */
    public function generatedLessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'lesson_plan_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LessonPlanVersion::class, 'lesson_plan_id')->orderByDesc('version');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'lesson_plan_id');
    }
}
