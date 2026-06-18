<?php

namespace App\Modules\System\Business\Models;

use App\Models\User;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Business extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'sys_business';

    protected $guarded = [];

    /**
     * Related tables that block deletion when they reference this business.
     *
     * @var array<string, string> table => business foreign key column
     */
    public const LINKED_TABLES = [
        'edu_students' => 'business_id',
        'edu_courses' => 'business_id',
        'edu_classes' => 'business_id',
        'fin_invoices' => 'business_id',
        'fin_payments' => 'business_id',
        'crm_parents' => 'business_id',
        'hr_teachers' => 'business_id',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
