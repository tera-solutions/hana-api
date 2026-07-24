<?php

namespace App\Modules\CRM\Lead\Models;

use App\Models\User;
use App\Modules\CRM\Lead\Enums\LeadStatus;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Student\Models\Student;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Branch\Models\Branch;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

/**
 * CRM lead / prospective customer (table `crm_leads`) — see lead.md.
 */
class Lead extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'crm_leads';

    protected $guarded = [];

    protected $casts = [
        'dob' => 'date',
        'suspended_at' => 'datetime',
        'next_appointment' => 'datetime',
    ];

    public const STATUS_PENDING = LeadStatus::Pending->value;

    public const STATUS_VERIFIED = LeadStatus::Verified->value;

    public const STATUS_CONSULTING = LeadStatus::Consulting->value;

    public const STATUS_STUDYING = LeadStatus::Studying->value;

    public const STATUS_INACTIVE = LeadStatus::Inactive->value;

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * "Người phụ trách" — the staff member who owns the lead.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Students linked to this lead (pivot table `crm_lead_students`).
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'crm_lead_students', 'lead_id', 'student_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    /**
     * The lead ↔ student link rows, managed as a first-class resource.
     */
    public function studentLinks(): HasMany
    {
        return $this->hasMany(LeadStudent::class, 'lead_id');
    }

    /**
     * Guardians of this lead (table `crm_lead_guardians`).
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(LeadGuardian::class, 'lead_id');
    }

    /**
     * Business tags attached to the lead (pivot `crm_lead_tags`).
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'crm_lead_tags', 'lead_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Courses the lead is interested in (pivot `crm_lead_courses`).
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'crm_lead_courses', 'lead_id', 'course_id')
            ->withTimestamps();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(LeadHistory::class, 'lead_id');
    }
}
