<?php

namespace App\Modules\Education\ClassRoom\Models;

use App\Modules\Education\ClassRoom\Enums\ClassStudentStatus;
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

    const STATUS_ACTIVE = ClassStudentStatus::Active->value;

    const STATUS_RESERVED = ClassStudentStatus::Reserved->value;

    const STATUS_COMPLETED = ClassStudentStatus::Completed->value;

    const STATUS_DROPPED = ClassStudentStatus::Dropped->value;

    const STATUS_TRANSFERRED_OUT = ClassStudentStatus::TransferredOut->value;

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
