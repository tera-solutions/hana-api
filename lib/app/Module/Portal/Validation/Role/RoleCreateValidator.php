<?php

namespace App\Module\Portal\Validation\Role;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class RoleCreateValidator extends TeraValidator implements ValidableInterface
{

  /**
   * Validation for creating a new collection
   *
   * @var array
   */
  protected $rules = [];

  protected $message = [];
}
