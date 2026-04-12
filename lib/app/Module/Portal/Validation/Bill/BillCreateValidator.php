<?php

namespace App\Module\Portal\Validation\Bill;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class BillCreateValidator extends TeraValidator implements ValidableInterface
{

  /**
   * Validation for creating a new collection
   *
   * @var array
   */
  protected $rules = [];

  protected $message = [];
}
