<?php

namespace App\Modules\Education\Assignment\Models;

use App\Modules\Education\Assignment\Enums\SubmissionStatus;
use App\Modules\Education\Student\Models\Student;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Package\Database\Concerns\HasAuditFields;

class AssignmentSubmission extends Model
{
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'edu_assignment_submissions';

    protected $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
        'result_published' => 'boolean',
    ];

    public const STATUS_ASSIGNED = SubmissionStatus::Assigned->value;

    public const STATUS_SUBMITTED = SubmissionStatus::Submitted->value;

    public const STATUS_LATE_SUBMITTED = SubmissionStatus::LateSubmitted->value;

    public const STATUS_GRADED = SubmissionStatus::Graded->value;

    public const STATUS_REVIEWED = SubmissionStatus::Reviewed->value;

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AssignmentSubmissionFile::class, 'submission_id');
    }
}
