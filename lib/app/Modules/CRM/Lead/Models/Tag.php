<?php

namespace App\Modules\CRM\Lead\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use App\Modules\System\Business\Models\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\BelongsToBusiness;
use Package\Database\Concerns\HasAuditFields;

/**
 * Business tag (table `crm_tags`) attachable to leads.
 */
class Tag extends Model
{
    use BelongsToBusiness;
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'crm_tags';

    protected $guarded = [];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
