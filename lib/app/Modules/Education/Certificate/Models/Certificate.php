<?php

namespace App\Modules\Education\Certificate\Models;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

class Certificate extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;

    protected $table = 'edu_certificates';

    protected $guarded = [];

    protected $casts = [
        'final_score' => 'decimal:2',
        'issued_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public const STATUS_ISSUED = 'issued';

    public const STATUS_REVOKED = 'revoked';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }
}
