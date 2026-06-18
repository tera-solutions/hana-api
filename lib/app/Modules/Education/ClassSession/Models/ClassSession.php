<?php

namespace App\Modules\Education\ClassSession\Models;

use App\Modules\CRM\Lead\Models\Tag;
use App\Modules\Education\Attendance\Models\Attendance;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassSchedule\Models\ClassSchedule;
use App\Modules\Education\ClassSession\Enums\ClassSessionStatus;
use App\Modules\Education\SessionFeedback\Models\SessionFeedback;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class ClassSession extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_sessions';

    protected $guarded = [];

    protected $casts = [
        'session_date' => 'date',
        'attendance_locked' => 'boolean',
        'revenue_amount' => 'decimal:2',
    ];

    const STATUS_UPCOMING = ClassSessionStatus::Upcoming->value;

    const STATUS_ONGOING = ClassSessionStatus::Ongoing->value;

    const STATUS_COMPLETED = ClassSessionStatus::Completed->value;

    const STATUS_CANCELLED = ClassSessionStatus::Cancelled->value;

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'schedule_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    /**
     * Business tags attached to the session (pivot `edu_session_tags`).
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'edu_session_tags', 'session_id', 'tag_id')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'session_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(SessionFeedback::class, 'session_id');
    }
}
