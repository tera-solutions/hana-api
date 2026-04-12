<?php

namespace App\Module\Portal\Validation\Member;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class MemberUpdateValidator extends TeraValidator implements ValidableInterface
{

  /**
   * Validation for creating a new collection
   *
   * @var array
   */
    protected $rules = [
        'email' => 'required',
        'phone' => 'required'
    ];

    protected $message = [
        'email.required' => 'Không cho phép để trống tên',
        'phone.required' => 'Không cho phép để trống số điện thoại'
    ];
}
