<?php

namespace App\Modules\Education\Attendance\Models;

use App\Modules\Education\Attendance\Enums\AttendanceStatus;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Student\Models\Student;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Attendance extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_attendances';

    protected $guarded = [];

    protected $casts = [
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
    ];

    const STATUS_PRESENT = AttendanceStatus::Present->value;

    const STATUS_ABSENT = AttendanceStatus::Absent->value;

    const STATUS_LATE = AttendanceStatus::Late->value;

    const STATUS_EXCUSED = AttendanceStatus::Excused->value;

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
