<?php

namespace App\Module\Portal\Validation\Notification;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class NotificationUpdateValidator extends TeraValidator implements ValidableInterface
{

    /**
     * Validation for creating a new collection
     *
     * @var array
     */
    protected $rules = [
        'title' => 'required',
        'content' => 'required',
    ];

    protected $message = [
        "title.required" => "Tiêu đề là trường bắt buộc!",
        "content.required" => "Nội dung  là trường bắt buộc!"
    ];
}
