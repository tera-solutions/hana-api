<?php

namespace App\Modules\Education\Material\Models;

use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Package\Database\Concerns\HasAuditFields;

class MaterialCategory extends Model
{
    use HasAuditFields;
    use LogsActivity;

    protected $table = 'edu_material_categories';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'category_id');
    }
}
