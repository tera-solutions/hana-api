<?php

namespace App\Modules\Education\ClassRoom\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class ClassSession extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'edu_sessions';

    protected $guarded = [];

    protected $casts = [
        'session_date' => 'date',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELED = 'canceled';

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'schedule_id');
    }
}
