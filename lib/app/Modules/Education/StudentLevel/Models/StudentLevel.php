<?php

namespace App\Modules\Education\StudentLevel\Models;

use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Level\Models\Level;
use App\Modules\Education\Student\Models\Student;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Package\Database\Concerns\HasAuditFields;

class StudentLevel extends Model
{
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'edu_student_levels';

    protected $guarded = [];

    protected $casts = [
        'course_id' => 'integer',
        'assigned_at' => 'datetime',
        'placement_score' => 'decimal:2',
    ];

    public const STATUS_ACTIVE = 'active';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(StudentLevelHistory::class, 'student_level_id');
    }
}
