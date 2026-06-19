<?php

namespace App\Modules\Education\Exam\Models;

use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Exam\Enums\ExamStatus;
use App\Modules\Education\Level\Models\Level;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Exam extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_exams';

    protected $guarded = [];

    protected $casts = [
        'total_score' => 'decimal:2',
        'passing_score' => 'decimal:2',
        'version' => 'integer',
    ];

    public const STATUS_DRAFT = ExamStatus::Draft->value;

    public const STATUS_PUBLISHED = ExamStatus::Published->value;

    public const STATUS_ARCHIVED = ExamStatus::Archived->value;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'exam_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class, 'exam_id');
    }
}
