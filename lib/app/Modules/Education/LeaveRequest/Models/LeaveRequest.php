<?php

namespace App\Modules\Education\LeaveRequest\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\LeaveRequest\Enums\LeaveReasonType;
use App\Modules\Education\LeaveRequest\Enums\LeaveRequestType;
use App\Modules\Education\LeaveRequest\Enums\LeaveStatus;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Package\Database\Concerns\HasAuditFields;

/**
 * A student/teacher request to be absent from a lesson (table `edu_leave_requests`).
 */
class LeaveRequest extends Model
{
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'edu_leave_requests';

    protected $guarded = [];

    protected $casts = [
        'leave_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public const TYPE_STUDENT = LeaveRequestType::StudentLeave->value;

    public const TYPE_TEACHER = LeaveRequestType::TeacherLeave->value;

    public const STATUS_PENDING = LeaveStatus::Pending->value;

    public const STATUS_APPROVED = LeaveStatus::Approved->value;

    public const STATUS_REJECTED = LeaveStatus::Rejected->value;

    public const STATUS_CANCELLED = LeaveStatus::Cancelled->value;

    public const STATUS_COMPLETED = LeaveStatus::Completed->value;

    /**
     * No deleted_at column — the lifecycle is status-based (leave-request.md §VI).
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

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function makeups(): HasMany
    {
        return $this->hasMany(MakeupLesson::class, 'leave_request_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LeaveRequestLog::class, 'leave_request_id');
    }

    public function isStudentLeave(): bool
    {
        return $this->request_type === self::TYPE_STUDENT;
    }

    public function reasonTypeLabel(): ?string
    {
        return LeaveReasonType::tryFrom((string) $this->reason_type)?->label();
    }
}
