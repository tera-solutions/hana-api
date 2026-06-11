<?php

namespace App\Modules\HR\Teacher\Models;

use App\Models\User;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Teacher extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'hr_teachers';

    protected $guarded = [];

    /**
     * Related tables that block deletion when they reference this teacher.
     *
     * @var array<string, string> table => teacher foreign key column
     */
    public const LINKED_TABLES = [
        'edu_class_teacher' => 'teacher_id',
        'hr_teaching_sessions' => 'teacher_id',
        'hr_contracts' => 'teacher_id',
        'hr_payrolls' => 'teacher_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
