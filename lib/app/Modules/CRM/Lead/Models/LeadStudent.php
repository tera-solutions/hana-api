<?php

namespace App\Modules\CRM\Lead\Models;

use App\Modules\Education\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * Lead ↔ Student relationship (pivot table `crm_lead_students`) managed as a
 * first-class resource (see lead.md §9 "Liên kết học viên").
 */
class LeadStudent extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'crm_lead_students';

    protected $guarded = [];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
