<?php

namespace App\Modules\System\Package\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'sys_packages';

    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'feature_keys' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
    ];
}
