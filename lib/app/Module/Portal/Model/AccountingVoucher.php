<?php

namespace App\Module\Portal\Model;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AccountingVoucher extends Model
{
  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  public $timestamps = false;

  protected $guarded = ['id'];

  /**
   * The table associated with the model.
   *
   * @var string
   */

  protected $connection = 'admin';
  protected $table = 'ad_accountant_vouchers';

  protected $fillable = [
    'ballot_type',
    'pay_method_type',
    'date',
    'accounting_date',
    'code',
    'submitter_id',
    'accounted_business_result',
    'note',
    'object_type',
    'created_by',
    'created_at',
    'updated_by',
    'updated_at'
  ];

  public function user_created()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function user_updated()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  public function explains()
  {
    return $this->hasMany(AccountingVoucherExplain::class, 'voucher_id');
  }
}
