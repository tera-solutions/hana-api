<?php

namespace App\Module\Portal\Model\Wallet;

use App\Module\Portal\Constants\CommonConstants;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $connection = 'admin';
  protected $table = 'ad_transactions';

  protected $fillable = [
    'id',
    'card_id',
    'transaction_code',
    'status',
    'transaction_type',
    'amount',
    'transaction_date',
    'created_at',
    'updated_at',
    'deleted_at',
    'created_by',
    'methods'
  ];
}
