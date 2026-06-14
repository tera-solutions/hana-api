<?php

namespace App\Modules\CRM\Parent\Models;

use App\Models\User;
use App\Modules\CRM\Parent\Enums\ParentStatus;
use App\Modules\Education\Student\Models\Student;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * Parent / guardian record (table `crm_parents`).
 *
 * Named `ParentModel` because `Parent` is a reserved word in PHP and cannot be
 * used as a class name.
 */
class ParentModel extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'crm_parents';

    protected $guarded = [];

    public const STATUS_ACTIVE = ParentStatus::Active->value;

    public const STATUS_SUSPENDED = ParentStatus::Suspended->value;

    public const STATUS_INACTIVE = ParentStatus::Inactive->value;

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

    public function histories(): HasMany
    {
        return $this->hasMany(ParentHistory::class, 'parent_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'crm_parent_student', 'parent_id', 'student_id')
            ->withPivot('relation')
            ->withTimestamps();
    }
}
