<?php

namespace App\Modules\Education\Exam\Models;

use App\Modules\Education\Student\Models\Student;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

class ExamResult extends Model
{
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'edu_exam_results';

    protected $guarded = [];

    protected $casts = [
        'listening_score' => 'decimal:2',
        'speaking_score' => 'decimal:2',
        'reading_score' => 'decimal:2',
        'writing_score' => 'decimal:2',
        'grammar_score' => 'decimal:2',
        'vocabulary_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'passed' => 'boolean',
        'published_at' => 'datetime',
    ];

    public const GRADE_EXCELLENT = 'excellent';

    public const GRADE_GOOD = 'good';

    public const GRADE_PASS = 'pass';

    public const GRADE_FAIL = 'fail';

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
