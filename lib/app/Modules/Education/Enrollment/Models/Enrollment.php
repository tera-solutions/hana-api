<?php

namespace App\Modules\Education\Enrollment\Models;

use App\Models\User;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Enrollment\Enums\EnrollmentStatus;
use App\Modules\Education\Student\Models\Student;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

class Enrollment extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_enrollments';

    protected $guarded = [];

    protected $casts = [
        'enrolled_at' => 'date',
        'total_lessons' => 'integer',
        'completed_lessons' => 'integer',
        'remaining_lessons' => 'integer',
        'price_per_lesson' => 'decimal:2',
        'tuition_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'debt_amount' => 'decimal:2',
    ];

    const STATUS_PENDING = EnrollmentStatus::Pending->value;

    const STATUS_STUDYING = EnrollmentStatus::Studying->value;

    const STATUS_SUSPENDED = EnrollmentStatus::Suspended->value;

    const STATUS_TRANSFERRED = EnrollmentStatus::Transferred->value;

    const STATUS_COMPLETED = EnrollmentStatus::Completed->value;

    const STATUS_CANCELLED = EnrollmentStatus::Cancelled->value;

    const STATUS_REFUNDED = EnrollmentStatus::Refunded->value;

    /**
     * Statuses considered "active" — they block a duplicate enrollment of the
     * same student into the same class (enrollment.md §6 "Trùng ghi danh").
     *
     * @var string[]
     */
    const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_STUDYING,
        self::STATUS_SUSPENDED,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(EnrollmentTransfer::class, 'enrollment_id')->latest();
    }

    public function suspensions(): HasMany
    {
        return $this->hasMany(EnrollmentSuspension::class, 'enrollment_id')->latest();
    }
}
