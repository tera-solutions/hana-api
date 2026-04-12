<?php

namespace App\Module\Portal\Model;

use App\Module\Portal\Constants\CommonConstants;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
  protected $table = 'sys_bills';

  protected $fillable = [
    'id',
    'customer_name',
    'customer_email',
    'customer_phone',
    'code',
    'methods',
    'status',
    'total_amount',
    'transaction_date',
    'note',
    'number_tax',
    'company_name',
    'address',
    'email_receive',
    'created_by',
    'created_at',
    'updated_at',
    'export_bill',
    'total_amount_product',
    'discount',
    'vat_tax',
    'type',
    'transaction_type',
    'card_id',
    'business_id',
    'start_up_fee'
  ];

  protected $appends = ['status_text', 'methods_text', 'allow_pay'];

  public function getStatusTextAttribute()
  {
    return CommonConstants::STATUS_BILL[$this->status] ?? null;
  }

  public function getAllowPayAttribute()
  {
    return $this->isPackageInconsistent();
    if (
      $this->isOverduePayment($this->transaction_date)
      || !$this->isPackageInconsistent()
      || !$this->isHavePackageDeleted()
      || !$this->isHaveServiceDeleted()
    ) {
      return 0;
    } else {
      return 1;
    }
  }

  public function isOverduePayment($transaction_date)
  {
    return now()->format('Y-m-d') > Carbon::parse($transaction_date)->format('Y-m-d') ? false : true;
  }

  public function isPackageInconsistent()
  {
    return (json_encode($this->getPackageOfBill()) == json_encode($this->getPackageOriginal())) ? true : false;
  }

  public function isHavePackageDeleted()
  {
    $items = $this
      ->items()
      ->get()
      ->toArray();

    $containsNullPackageId = array_filter($items, function ($item) {
      return $item['package_id'] == null;
    });

    return empty($containsNullPackageId) ? true : false;
  }

  public function isHaveServiceDeleted()
  {
    $items = $this
      ->items()
      ->with(['package.service'])
      ->get()
      ->toArray();

    $containsNullServiceId = array_filter($items, function ($item) {
      if (isset($item['package'])) {
        return $item['package']['service'] == null;
      }
    });

    return empty($containsNullServiceId) ? true : false;
  }

  public function getPackageOfBill()
  {
    $items = $this
      ->items()
      ->get()
      ->toArray();

    usort($items, function ($a, $b) {
      return $a['package_id'] <=> $b['package_id'];
    });

    return array_map(function ($item) {
      return [
        'package_id' => $item['package_id'],
        'users_count' => $item['users_count'],
        'size' => $item['size'],
        'orders' => $item['orders'],
        'time' => $item['time'],
        'price' => $item['price']
      ];
    }, $items);
  }

  public function getPackageOriginal()
  {
    $packages = $this
      ->items()
      ->with(['package'])
      ->get()
      ->toArray();

    usort($packages, function ($a, $b) {
      return $a['package']['id'] <=> $b['package']['id'];
    });

    return array_map(function ($item) {
      if (isset($item['package'])) {
        return [
          'package_id' => $item['package']['id'],
          'users_count' => $item['package']['quantity_user'],
          'size' => $item['package']['quantity_capacity'],
          'orders' => $item['package']['quantity_order'],
          'time' => $item['package']['time'],
          'price' => $item['package']['price']
        ];
      }
    }, $packages);
  }


  public function getMethodsTextAttribute()
  {
    return CommonConstants::METHOD_PAYMENT[$this->methods] ?? null;
  }

  public function transactions()
  {
    return $this->hasMany(BillTransaction::class, 'bill_id');
  }

  public function items()
  {
    return $this->hasMany(BillItem::class, 'bill_id');
  }

  public function customer()
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
