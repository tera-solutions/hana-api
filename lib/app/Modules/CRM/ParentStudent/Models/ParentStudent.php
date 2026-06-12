<?php

namespace App\Modules\CRM\ParentStudent\Models;

use App\Modules\CRM\Parent\Models\ParentModel;
use App\Modules\Education\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * Parent ↔ Student relationship (pivot table `crm_parent_student`) managed as a
 * first-class resource (see parent-student.md).
 */
class ParentStudent extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'crm_parent_student';

    protected $guarded = [];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'is_billing_contact' => 'boolean',
        'is_pickup_authorized' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
