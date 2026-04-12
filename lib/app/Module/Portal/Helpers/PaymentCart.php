<?php

namespace App\Module\Portal\Helpers;

use App\Module\Portal\Model\Cart as ModelCart;
use Illuminate\Support\Facades\Auth;

class PaymentCart
{
  public const VAT_RATIO = 8; // vat 8%

  public $codeDiscount;

  public $items;

  public $info = [
    'total_amount_product' => 0,
    'discount' => 0,
    'vat_tax' => 0,
    'total_amount' => 0,
    'start_up_fee' => 0
  ];

  public function setItems($items)
  {
    $this->items = $items;
    return $this;
  }

  public function applyCodeDiscount(string $code)
  {
    // handle code discount
    return $this;
  }

  public function getInformationPayment($isPlusStartUp = false)
  {
    $startUpFee = 0;
    $totalProduct = $this->items->sum('total_amount');
    $vat = (self::VAT_RATIO / 100) * $totalProduct;
    $this->info['total_amount_product'] = $totalProduct;
    $this->info['vat_tax'] = round($vat);
    if ($isPlusStartUp) {
      foreach (collect($this->items)->toArray() as $key => $value) {
        if (isset($value['package'])) {
          if (isset($value['package']['service'])) {
            $startUpFee += $value['package']['service']['start_up_fee'];
          }
        }
      }
    }
    $startUpFee = round($startUpFee);
    $this->info['start_up_fee'] = $startUpFee;
    $this->info['total_amount'] = round($totalProduct + $vat + $startUpFee);
    return $this->info;
  }
  //
}
