<?php

namespace App\Module\Portal\Model;

use App\Module\Portal\Model\RolePermission as ModelRolePermission;
use App\Module\Portal\Permission\RolePermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $table = 'roles';

    public function permissions()
    {
        return $this->hasMany(ModelRolePermission::class, 'role_id');
    }
}
