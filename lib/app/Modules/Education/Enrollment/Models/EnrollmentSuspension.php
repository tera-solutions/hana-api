<?php

namespace App\Modules\Education\Enrollment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

class EnrollmentSuspension extends Model
{
    use HasAuditFields;

    protected $table = 'edu_enrollment_suspensions';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getAuditColumns(): array
    {
        return ['created_by', 'updated_by'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }
}
