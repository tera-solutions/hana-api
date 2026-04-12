<?php

namespace App\Module\Portal\Model;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PhpParser\Node\Expr\Cast\Object_;

class BillItem extends Model
{
  protected $table = 'sys_bill_items';

  protected $fillable = [
    'id',
    'bill_id',
    'package_name',
    'quantity',
    'total_time',
    'price',
    'old_package',
    'total_amount',
    'service_name',
    'created_at',
    'updated_at',
    'package_id',
    'users_count',
    'size',
    'orders',
    'time'
  ];

  protected $appends = ['amount', 'service_name_original', 'package_name_original', 'quantity_capacity', 'quantity_order', 'quantity_user'];

  public function getAmountAttribute()
  {
    return $this->price;
  }

  public function package()
  {
    return $this->belongsTo(PackageService::class, 'package_id');
  }


  public function getServiceNameOriginalAttribute()
  {
    if ($this->package) {
      if ($this->package->service) {
        return $this->package->service->name;
      }
    }

    return $this->service_name;
  }

  public function getPackageNameOriginalAttribute()
  {
    return $this->package ? $this->package->name : $this->package_name;
  }

  public function getQuantityCapacityAttribute()
  {
    return $this->size;
  }

  public function getQuantityOrderAttribute()
  {
    return $this->orders;
  }

  public function getQuantityUserAttribute()
  {
    return $this->users_count;
  }
}
