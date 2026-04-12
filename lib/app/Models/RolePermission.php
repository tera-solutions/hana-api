<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePermission extends Model
{
    protected $guarded = ['id'];
    protected $table = "role_has_permissions";

    public function role()
    {
        return $this->belongsTo(\App\Models\Role::class, 'role_id');
    }
}
