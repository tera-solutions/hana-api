<?php

namespace App\Module\Portal\Validation\Member;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class MemberCreateValidator extends TeraValidator implements ValidableInterface
{

  /**
   * Validation for creating a new collection
   *
   * @var array
   */
  protected $rules = [];

  protected $message = [];
}
