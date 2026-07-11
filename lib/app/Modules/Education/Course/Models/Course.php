<?php

namespace App\Modules\Education\Course\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Course extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_courses';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
        'price_per_lesson' => 'decimal:2',
    ];

    /**
     * Related tables that block hard-deletion / code changes when they reference
     * this course.
     *
     * @var array<string, string> table => course foreign key column
     */
    public const LINKED_TABLES = [
        'edu_classes' => 'course_id',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(CourseHistory::class, 'course_id');
    }

    public function curriculums(): HasMany
    {
        return $this->hasMany(CourseCurriculum::class, 'course_id')->orderBy('order');
    }
}
