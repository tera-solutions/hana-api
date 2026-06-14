<?php

namespace App\Modules\Education\Enrollment\Models;

use App\Models\User;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Enrollment extends Model
{
    use HasAuditFields;
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

    const STATUS_PENDING = 'pending';

    const STATUS_STUDYING = 'studying';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_TRANSFERRED = 'transferred';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REFUNDED = 'refunded';

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
