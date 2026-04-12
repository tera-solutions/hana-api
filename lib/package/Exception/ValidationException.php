<?php

namespace Package\Exception;

use Illuminate\Http\Response;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */
class ValidationException extends TeraException
{
    public function __construct($message, $code = 422, $errors = [])
    {
        if (!$message) {
            $message = "Lỗi validation dữ liệu";
        }

        parent::__construct($message, $code, $errors);
    }
}
