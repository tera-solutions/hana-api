<?php

namespace App\Modules\Education\ClassRoom\Models;

use App\Modules\Education\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class ClassStudent extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'edu_class_students';

    protected $guarded = [];

    protected $casts = [
        'enrolled_at' => 'date',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_RESERVED = 'reserved';

    const STATUS_COMPLETED = 'completed';

    const STATUS_DROPPED = 'dropped';

    const STATUS_TRANSFERRED_OUT = 'transferred_out';

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
