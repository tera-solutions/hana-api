<?php

namespace App\Module\Portal\Model\Wallet;

use Illuminate\Database\Eloquent\Model;

class CardType extends Model
{
  protected $connection = 'admin';
  protected $table = 'ad_card_types';
}
