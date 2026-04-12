<?php

namespace App\Module\Portal\Model;

use App\Models\Role;
use App\Module\Portal\Model\Role as ModelRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
  protected $connection = 'admin';

  protected $table = 'ad_services';

  public $timestamps = false;

  protected $fillable = [
    'id',
    'code',
    'name',
    'type',
    'media_id',
    'link_file',
    'file_name',
    'status',
    'description',
    'role_id',
    'created_at',
    'updated_at',
    'created_by',
    'updated_by',
    'start_up_fee'
  ];

  protected $appends = ['file_url'];

  public function getFileUrlAttribute()
  {
    if ($this->link_file) {
      return $this->link_file;
    }
    return null;
  }

  public function packages()
  {
    return $this->hasMany(PackageService::class, 'service_id');
  }

  public function businesses()
  {
    return $this->hasMany(BusinessService::class, 'service_id');
  }

  public function role()
  {
    return $this->belongsTo(ModelRole::class, 'role_id');
  }
}
