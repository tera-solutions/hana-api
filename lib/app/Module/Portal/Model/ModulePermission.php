<?php

namespace App\Module\Portal\Model;

use App\Models\User;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModulePermission extends Model
{
  

  protected $guarded = ['id'];

  protected $table = "module_permissions";

  protected $fillable = [
    'id',
    'module_id',
    'type',
    'user_id',
    'created_at',
    'updated_at',
  ];

  public function module()
  {
    return $this->belongsTo(Module::class, 'module_id');
  }
}
