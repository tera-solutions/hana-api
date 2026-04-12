<?php

namespace Package\Exception;

use Illuminate\Http\Response;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */
class AuthorizationException extends TeraException
{
    public function __construct($message = '', $code = 403)
    {
        if (!$message) {
            $message = "Bạn không có quyền sử dụng chức năng này!";
        }
        parent::__construct($message, $code);
    }
}
