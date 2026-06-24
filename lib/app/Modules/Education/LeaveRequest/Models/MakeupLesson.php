<?php

namespace App\Modules\Education\LeaveRequest\Models;

use App\Modules\Education\LeaveRequest\Enums\MakeupStatus;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

/**
 * A make-up entitlement / scheduled make-up session (table `edu_makeup_lessons`).
 */
class MakeupLesson extends Model
{
    use HasAuditFields;

    protected $table = 'edu_makeup_lessons';

    protected $guarded = [];

    public const STATUS_WAITING = MakeupStatus::Waiting->value;

    public const STATUS_SCHEDULED = MakeupStatus::Scheduled->value;

    public const STATUS_COMPLETED = MakeupStatus::Completed->value;

    public const STATUS_EXPIRED = MakeupStatus::Expired->value;

    /**
     * @return string[]
     */
    public function getAuditColumns(): array
    {
        return ['created_by', 'updated_by'];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function originalLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'original_lesson_id');
    }

    public function makeupLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'makeup_lesson_id');
    }
}
