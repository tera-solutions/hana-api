<?php

namespace App\Module\Portal\Model;

use App\Module\Portal\Model\Role;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePermission extends Model
{
    
    protected $guarded = ['id'];
    protected $table = "role_has_permissions";

    protected $fillable = [
        'id',
        'permission_id',
        'role_id',
        'code',
        'created_at',
        'updated_at',
        'created_by'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function groupControl()
    {
        return $this->belongsTo(GroupPageControl::class, 'permission_id');
    }
}
