<?php

namespace Package\Exception;

use Illuminate\Http\Response;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */
class AuthenticationException extends TeraException
{
    public function __construct($message = '', $code = 401)
    {
        if (!$message) {
            $message = "Không có quyền truy cập!";
        }
        parent::__construct($message, $code);
    }
}
