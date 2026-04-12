<?php

namespace App\Module\Portal\Validation\BussinessLocation;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class BussinessLocationUpdateValidator extends TeraValidator implements ValidableInterface
{

  /**
   * Validation for creating a new User
   *
   * @var array
   */
    protected $rules = [
        'name' => 'required',
        'mobile' => 'required',
    ];

    protected $message = [
        "name.required" => "Vui lòng nhập tên chi nhánh!",
        "mobile.required" => "Vui lòng nhập số điện thoại chi nhánh!"
    ];
}
