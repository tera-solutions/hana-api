<?php

namespace Package\Exception;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Created by TeraCore.
 * User: truong.nq
 * Date: 5/12/2020
 * Time: 2:45 PM
 */
abstract class TeraException extends Exception
{
    protected $code;
    protected $message;
    protected $errors = [];

    public function __construct($message = null, $code = Response::HTTP_INTERNAL_SERVER_ERROR, $errors = [])
    {
        $this->code = $code;
        $this->errors = $errors;
        $this->message = $message ?: 'Server Exception';

        parent::__construct($message, $code);
    }

    public function render(Request $request)
    {
        $json = [
            'code' => $this->code,
            'msg' => $this->message,
            'errors' => $this->errors,
            'data' => null,
        ];

        return new JsonResponse($json);
    }

    public function report()
    {
        Log::emergency($this->message);
    }
}
