<?php

namespace App\Modules\Education\ClassRoom\Models;

use App\Models\User;
use App\Modules\Education\ClassSchedule\Models\ClassSchedule;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Course\Models\Course;
use App\Modules\HR\Teacher\Models\Teacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class ClassRoom extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'edu_classes';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'use_course_curriculum' => 'boolean',
        'min_warning_capacity' => 'integer',
        'min_capacity' => 'integer',
        'max_warning_capacity' => 'integer',
        'max_capacity' => 'integer',
    ];

    const STATUS_DRAFT = 'draft';

    const STATUS_UPCOMING = 'upcoming';

    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_COMPLETED = 'completed';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'class_id')->orderBy('weekday')->orderBy('start_time');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'class_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'class_id');
    }
}
