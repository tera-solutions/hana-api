<?php

namespace App\Modules\Education\Exam\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Exam\Enums\ExamSessionStatus;
use App\Modules\Education\Room\Models\Room;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class ExamSession extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_exam_sessions';

    protected $guarded = [];

    protected $casts = [
        'exam_date' => 'date',
    ];

    public const STATUS_SCHEDULED = ExamSessionStatus::Scheduled->value;

    public const STATUS_IN_PROGRESS = ExamSessionStatus::InProgress->value;

    public const STATUS_CLOSED = ExamSessionStatus::Closed->value;

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ExamRegistration::class, 'exam_session_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'exam_session_id');
    }
}
