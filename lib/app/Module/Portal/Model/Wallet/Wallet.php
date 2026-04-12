<?php

namespace App\Module\Portal\Model\Wallet;

use App\Module\Portal\Constants\CommonConstants;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $connection = 'admin';
  protected $table = 'ad_wallets';

  protected $fillable = [
    'id',
    'user_id',
    'original_amount',
    'marketing_amount',
    'availability_amount',
    'promotion_amount',
    'created_at',
    'updated_at'
  ];

  public function transactionsCompleted()
  {
    return $this->hasMany(Transaction::class, 'created_by', 'user_id');
  }
}
