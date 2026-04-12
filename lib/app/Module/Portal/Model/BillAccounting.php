<?php

namespace App\Module\Portal\Model;

use Illuminate\Database\Eloquent\Model;

class BillAccounting extends Model
{
  protected $table = 'sys_bill_accounting';

  protected $fillable = [
    'id',
    'bill_id',
    'voucher_id',
    'created_at',
    'updated_at'
  ];

  public function bill()
  {
    return $this->belongsTo(Bill::class, 'bill_id');
  }

  public function voucher()
  {
    return $this->belongsTo(AccountingVoucher::class, 'voucher_id');
  }
}
