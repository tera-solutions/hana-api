<?php

namespace App\Modules\Education\Material\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Package\Database\Concerns\HasAuditFields;

class MaterialMapping extends Model
{
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'edu_material_mappings';

    protected $guarded = [];

    protected $casts = [
        'entity_id' => 'integer',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
