<?php

namespace App\Modules\Education\Assignment\Models;

use App\Modules\Education\Assignment\Enums\AssignmentStatus;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Assignment extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_assignments';

    protected $guarded = [];

    protected $casts = [
        'max_score' => 'decimal:2',
        'due_date' => 'datetime',
        'allow_late_submission' => 'boolean',
        'allow_multiple_submission' => 'boolean',
    ];

    public const STATUS_DRAFT = AssignmentStatus::Draft->value;

    public const STATUS_PUBLISHED = AssignmentStatus::Published->value;

    public const STATUS_CLOSED = AssignmentStatus::Closed->value;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(AssignmentTarget::class, 'assignment_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }
}
