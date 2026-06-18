<?php

namespace App\Modules\Education\Material\Models;

use App\Modules\Education\Material\Enums\MaterialAccessType;
use App\Modules\Education\Material\Enums\MaterialStatus;
use App\Modules\System\ActivityLog\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

class Material extends Model
{
    use HasAuditFields;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'edu_materials';

    protected $guarded = [];

    protected $casts = [
        'current_version' => 'integer',
    ];

    public const STATUS_DRAFT = MaterialStatus::Draft->value;

    public const STATUS_ACTIVE = MaterialStatus::Active->value;

    public const ACCESS_INTERNAL = MaterialAccessType::Internal->value;

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MaterialVersion::class, 'material_id')->orderByDesc('version');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(MaterialMapping::class, 'material_id');
    }
}
