<?php

namespace App\Modules\Education\Material\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialVersion extends Model
{
    public $timestamps = false;

    protected $table = 'edu_material_versions';

    protected $guarded = [];

    protected $casts = [
        'version' => 'integer',
        'file_id' => 'integer',
        'file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
