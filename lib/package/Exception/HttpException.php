<?php

namespace Package\Exception;

use Illuminate\Http\Response;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */
class HttpException extends TeraException
{
    protected $code;
    protected $message;
    protected $errors = [];

    public function __construct($message, $code = 500, $errors = [])
    {
        $this->code = $code;
        $this->errors = $errors;
        $this->message = $message ?: 'Server Exception';

        parent::__construct($message, $code, $errors);
    }

    public function getStatusCode()
    {
        return $this->code;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
