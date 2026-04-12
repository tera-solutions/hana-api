<?php

namespace App\Module\Portal\Model;

use Illuminate\Database\Eloquent\Model;

class GroupRolePermission extends Model
{
  

  protected $table = 'role_has_permissions';

  protected $fillable = [
    'id',
    'permission_id',
    'role_id',
    'code',
    'created_at',
    'updated_at',
    'created_by'
  ];
  public function groupRole()
  {
    return $this->belongsTo(GroupRole::class, 'role_id');
  }

  public function groupControl()
  {
    return $this->belongsTo(GroupPageControl::class, 'permission_id');
  }
}
