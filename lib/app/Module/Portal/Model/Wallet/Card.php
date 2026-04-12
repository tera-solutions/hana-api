<?php

namespace App\Module\Portal\Model\Wallet;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
  protected $connection = 'admin';
  protected $table = 'ad_cards';

  public function cardType()
  {
    return $this->belongsTo(CardType::class, 'card_type_id');
  }
}
