<?php

namespace App\Module\Portal\Model\Wallet;

use App\Module\Portal\Constants\CommonConstants;
use Illuminate\Database\Eloquent\Model;

class TransactionVerified extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'sys_transaction_verified';

  protected $fillable = [
    'id',
    'session_id',
    'user_id',
    'created_at',
    'updated_at'
  ];
}
