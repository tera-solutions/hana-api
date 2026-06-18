<?php

namespace App\Modules\Education\Enrollment\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

class EnrollmentTransfer extends Model
{
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'edu_enrollment_transfers';

    protected $guarded = [];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function getAuditColumns(): array
    {
        return ['created_by', 'updated_by'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function fromClass(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'from_class_id');
    }

    public function toClass(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'to_class_id');
    }
}
