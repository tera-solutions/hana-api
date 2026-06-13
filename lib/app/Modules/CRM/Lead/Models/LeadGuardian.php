<?php

namespace App\Modules\CRM\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * Guardian of a lead (table `crm_lead_guardians`) — see lead.md §8
 * "Quản lý Người giám hộ". A lead may have several guardians.
 */
class LeadGuardian extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'crm_lead_guardians';

    protected $guarded = [];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
