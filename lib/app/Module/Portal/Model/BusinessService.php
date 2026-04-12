<?php

namespace App\Module\Portal\Model;

use App\Module\Portal\Model\Business;
use Illuminate\Database\Eloquent\Model;

class BusinessService extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $connection = 'admin';

  protected $table = 'ad_business_services';
  protected $guarded = ['id'];

  protected $fillable = [
    'business_id',
    'service_id',
    'package_id',
    'service_type',
    'date_active',
    'date_expired',
    'status',
    'created_at',
    'updated_at',
    'invoice',
    'time'
  ];

  public function service()
  {
    return $this->belongsTo(Service::class, 'service_id');
  }

  public function business()
  {
    return $this->belongsTo(Business::class, 'business_id');
  }

  public function package()
  {
    return $this->belongsTo(PackageService::class, 'package_id');
  }
}
