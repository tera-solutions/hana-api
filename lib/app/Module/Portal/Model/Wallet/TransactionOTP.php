<?php

namespace App\Module\Portal\Model\Wallet;

use App\Module\Portal\Constants\CommonConstants;
use Illuminate\Database\Eloquent\Model;

class TransactionOTP extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'sys_transaction_otp';

  protected $fillable = [
    'id',
    'otp_code',
    'expired',
    'created_at',
    'updated_at',
    'user_id',
    'session_id'
  ];
}
