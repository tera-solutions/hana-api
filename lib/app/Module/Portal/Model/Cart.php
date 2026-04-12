<?php

namespace App\Module\Portal\Model;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
  protected $table = 'sys_cart';

  protected $fillable = [
    'id',
    'user_id',
    'package_id',
    'quantity',
    'total_time',
    'amount',
    'old_package',
    'total_amount',
    'created_at',
    'updated_at'
  ];

  protected $appends = ['allow_change_item'];

  public function getAllowChangeItemAttribute()
  {
    $package = $this->package()->with(['service.packages'])->first();
    if (!$package->service) {
      return 0;
    }
    if (empty($package->service->packages)) {
      return 0;
    }

    $arrayAllowChange = array_filter(collect($package->service->packages)->toArray(), function ($item) {
      return $item['status'] == 1 && $item['id'] != $this->id;
    });

    return !empty($arrayAllowChange) ? 1 : 0;
  }

  public function package()
  {
    return $this->belongsTo(PackageService::class, 'package_id');
  }
}
