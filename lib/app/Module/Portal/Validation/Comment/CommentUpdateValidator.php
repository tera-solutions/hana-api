<?php

namespace App\Module\Portal\Validation\Comment;

use Package\Validation\ValidableInterface;
use Package\Validation\Source\TeraValidator;

class CommentUpdateValidator extends TeraValidator implements ValidableInterface
{

    /**
     * Validation for creating a new collection
     *
     * @var array
     */
    protected $rules = [
        'content' => 'required',
        'object_id' => 'required',
    ];

    protected $message = [
        "content.required" => "Vui lòng nhập nội dung bình luận!",
        "object_id.required" => "Vui lòng nhập ID dự án !"
    ];
}
