<?php

namespace App\Modules\Education\SessionFeedback\Models;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Student\Models\Student;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class SessionFeedback extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_session_feedbacks';

    protected $guarded = [];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
