<?php

namespace App\Module\Portal\Validation\Business;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class BusinessCreateValidation extends TeraValidator implements ValidableInterface
{

  /**
   * Validation for creating a new collection
   *
   * @var array
   */
  protected $rules = [];

  protected $message = [];
}
