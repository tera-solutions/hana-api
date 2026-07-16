<?php

namespace App\Modules\CRM\Parent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\BelongsToBusiness;

class ParentHistory extends Model
{
    use BelongsToBusiness;

    protected $table = 'crm_parent_histories';

    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }
}
