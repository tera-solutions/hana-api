<?php

namespace App\Modules\HR\Teacher\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class TeacherCertificate extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'hr_teacher_certificates';

    protected $guarded = [];

    protected $casts = [
        'issued_date' => 'date',
        'expired_date' => 'date',
    ];

    /** Days-to-expiry threshold for the "expiring soon" warning (teacher.md §7). */
    public const EXPIRY_WARNING_DAYS = 30;

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function isExpired(): bool
    {
        return $this->expired_date !== null && $this->expired_date->isPast();
    }

    public function isExpiringSoon(): bool
    {
        return $this->expired_date !== null
            && ! $this->isExpired()
            && $this->expired_date->lessThanOrEqualTo(now()->addDays(self::EXPIRY_WARNING_DAYS));
    }
}
