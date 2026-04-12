<?php

namespace Package\Exception;

use Illuminate\Http\Response;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */
class DatabaseException extends TeraException
{
    public function __construct($message = '', $code = 502)
    {
        if (!$message) {
            $message = "Lỗi hệ thống";
        }

        if (!$code) {
            $code = Response::HTTP_NOT_FOUND;
        }
        parent::__construct($message, $code);
    }
}
