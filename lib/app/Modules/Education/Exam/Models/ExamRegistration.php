<?php

namespace App\Modules\Education\Exam\Models;

use App\Modules\Education\Exam\Enums\RegistrationStatus;
use App\Modules\Education\Student\Models\Student;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

class ExamRegistration extends Model
{
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'edu_exam_registrations';

    protected $guarded = [];

    public const STATUS_REGISTERED = RegistrationStatus::Registered->value;

    public const STATUS_IN_PROGRESS = RegistrationStatus::InProgress->value;

    public const STATUS_SUBMITTED = RegistrationStatus::Submitted->value;

    public const STATUS_ABSENT = RegistrationStatus::Absent->value;

    public const STATUS_GRADED = RegistrationStatus::Graded->value;

    public const STATUS_PUBLISHED = RegistrationStatus::Published->value;

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
