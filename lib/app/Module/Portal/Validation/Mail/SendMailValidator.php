<?php

namespace App\Module\Portal\Validation\Mail;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class SendMailValidator extends TeraValidator implements ValidableInterface
{

    /**
     * Validation for creating a new collection
     *
     * @var array
     */
    protected $rules = [
        'email' => 'required|email'
    ];

    protected $message = [
        'email.required' => 'Vui lòng nhập email',
        'email.email' => 'Nhập một email không hợp lệ',
    ];
}
