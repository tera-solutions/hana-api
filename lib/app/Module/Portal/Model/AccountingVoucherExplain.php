<?php

namespace App\Module\Portal\Model;

use Illuminate\Database\Eloquent\Model;

class AccountingVoucherExplain extends Model
{
  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $connection = 'admin';
  protected $guarded = ['id'];

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'ad_voucher_explains';

  protected $fillable = [
    'voucher_id',
    'explain',
    'amount',
    'created_at',
    'updated_at',
  ];

  public function voucher()
  {
    return $this->belongsTo(AccountingVoucher::class, 'voucher_id');
  }
}
