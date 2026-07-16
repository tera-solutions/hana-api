<?php

namespace App\Modules\CRM\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;

/**
 * Audit-trail entry for a lead (table `crm_lead_histories`) — captures creation,
 * updates, status changes, owner changes and suspend/restore (lead.md §5 history).
 */
class LeadHistory extends Model
{
    use BelongsToBusiness;

    protected $table = 'crm_lead_histories';

    protected $guarded = [];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
