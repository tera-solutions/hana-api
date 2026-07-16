<?php

namespace App\Modules\System\Branch\Models;

use App\Models\User;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

class Branch extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'sys_branches';

    protected $guarded = [];

    /**
     * Related tables that block deletion when they reference this branch.
     *
     * Only edu_students carries a branch_id in the current schema; the other
     * entities the spec mentions are scoped to the Business, not the Branch.
     *
     * @var array<string, string> table => branch foreign key column
     */
    public const LINKED_TABLES = [
        'edu_students' => 'branch_id',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
