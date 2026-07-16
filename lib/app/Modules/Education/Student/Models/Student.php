<?php

namespace App\Modules\Education\Student\Models;

use App\Models\User;
use App\Modules\CRM\Parent\Models\ParentModel;
use App\Modules\Education\Level\Models\Level;
use App\Modules\Education\Student\Enums\StudentStatus;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;
use Package\Database\Concerns\HasAvatarUrl;

class Student extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use HasAvatarUrl;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_students';

    protected $guarded = [];

    protected $appends = ['avatar_url'];

    public const STATUS_ACTIVE = StudentStatus::Active->value;

    public const STATUS_SUSPENDED = StudentStatus::Suspended->value;

    public const STATUS_GRADUATED = StudentStatus::Graduated->value;

    public const STATUS_DROPPED = StudentStatus::Dropped->value;

    /**
     * Related tables that block hard-deletion when they reference this student.
     * Missing tables/columns are treated as "no linked data" by the delete guard.
     *
     * @var array<string, string> table => student foreign key column
     */
    public const LINKED_TABLES = [
        'edu_enrollments' => 'student_id',
        'fin_invoices' => 'student_id',
        'fin_payments' => 'student_id',
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

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(StudentProfile::class, 'student_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(StudentHistory::class, 'student_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentModel::class, 'crm_parent_student', 'student_id', 'parent_id')
            ->withPivot('relation')
            ->withTimestamps();
    }
}
