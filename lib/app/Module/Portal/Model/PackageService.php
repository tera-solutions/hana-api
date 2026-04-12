<?php

namespace App\Module\Portal\Model;

use Illuminate\Database\Eloquent\Model;

class PackageService extends Model
{
  protected $connection = 'admin';

  protected $table = 'ad_package_services';

  protected $fillable = [
    'id',
    'service_id',
    'name',
    'time',
    'quantity_user',
    'quantity_order',
    'quantity_capacity',
    'price',
    'status',
    'created_at',
    'updated_at',
  ];

  public function service()
  {
    return $this->belongsTo(Service::class, 'service_id');
  }
}
