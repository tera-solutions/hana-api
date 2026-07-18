<?php

namespace App\Modules\HR\Teacher\Models;

use App\Models\User;
use App\Modules\Finance\BankAccount\Models\BankAccount;
use App\Modules\HR\Teacher\Enums\TeacherStatus;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

class Teacher extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'hr_teachers';

    protected $guarded = [];

    protected $casts = [
        'dob' => 'date',
        'joined_at' => 'date',
        'resigned_at' => 'date',
        'hourly_rate' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
    ];

    public const STATUS_ACTIVE = TeacherStatus::Active->value;

    public const STATUS_SUSPENDED = TeacherStatus::Suspended->value;

    public const STATUS_RESIGNED = TeacherStatus::Resigned->value;

    /**
     * Related tables that block hard-deletion / resignation when they reference
     * this teacher.
     *
     * @var array<string, string> table => teacher foreign key column
     */
    public const LINKED_TABLES = [
        'edu_class_teacher' => 'teacher_id',
        'edu_sessions' => 'teacher_id',
        'hr_contracts' => 'teacher_id',
        'hr_payrolls' => 'teacher_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(TeacherSkill::class, 'teacher_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(TeacherCertificate::class, 'teacher_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TeacherHistory::class, 'teacher_id');
    }

    public function bankAccount(): MorphOne
    {
        return $this->morphOne(BankAccount::class, 'owner');
    }
}
