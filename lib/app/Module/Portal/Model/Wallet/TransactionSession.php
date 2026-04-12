<?php

namespace App\Module\Portal\Model\Wallet;

use App\Module\Portal\Constants\CommonConstants;
use Illuminate\Database\Eloquent\Model;

class TransactionSession extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'sys_transaction_sessions';

  protected $fillable = [
    'id',
    'time',
    'created_at',
    'updated_at'
  ];
}
