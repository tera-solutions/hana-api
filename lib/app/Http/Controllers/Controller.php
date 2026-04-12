<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $statusCode;

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function respondWithError($message = null, $errors = [], $status = 500)
    {
        $data = [
            'success' => false,
            'msg' => $message,
            'data' => null,
            'code' => $status,
            'errors' => $errors
        ];

        return response()->json($data, 200);
    }

    public function respondWithServerError($message = null, $errors = [], $status = 500)
    {
        $data = [
            'success' => false,
            'msg' => $message,
            'data' => null,
            'code' => $status,
            'errors' => $errors
        ];

        return response()->json($data, $status);
    }

    /**
     * Returns a Unauthorized response.
     *
     * @param  string  $message
     * @return \Illuminate\Http\Response
     */
    public function respondUnauthorized($message = 'Unauthorized action.')
    {
        $httpCode = 403;

        return $this->setStatusCode($httpCode)
            ->respondWithError($message, [], $httpCode);
    }

    /**
     * Returns a went wrong response.
     *
     * @param  object  $exception = null
     * @return \Illuminate\Http\Response
     */
    public function respondWentWrong($exception = null)
    {
        //If debug is enabled then send exception message
        $message = (config('app.debug') && is_object($exception)) ? "File:" . $exception->getFile() . "Line:" . $exception->getLine() . "Message:" . $exception->getMessage() : __('messages.something_went_wrong');

        //TODO: show exception error message when error is enabled.
        return $this->setStatusCode(200)
            ->respondWithError($message);
    }

    /**
     * Returns a 200 response.
     *
     * @param  object  $message = null
     * @return \Illuminate\Http\Response
     */
    public function respondSuccess($data = null, $message = null, $additional_data = [])
    {
        $message = is_null($message) ? "Thao tác thành công" : $message;
        $data = [
            'success' => true,
            'msg' => $message,
            'data' => $data,
            'code' => 200,
            'errors' => null
        ];

        if (!empty($additional_data)) {
            $data = array_merge($data, $additional_data);
        }

        return $this->respond($data);
    }

    /**
     * Returns a 200 response.
     *
     * @param  array  $data
     * @return \Illuminate\Http\Response
     */
    public function respond($data)
    {
        return response()->json($data);
    }

    public function CryptoJSAesDecrypt($data, $passphrase = null)
    {
        try {
            $salt = hex2bin($data["salt"]);
            $iv = hex2bin($data["iv"]);
        } catch (Exception $e) {
            return null;
        }

        $ciphertext = base64_decode($data["da"]);
        $iterations = 999; //same as js encrypting

        if (empty($passphrase)) {
            $passphrase = env("AUTH_PRIVATE_KEY");
        }

        $key = hash_pbkdf2("sha256", $passphrase, $salt, $iterations, 64);

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', hex2bin($key), OPENSSL_RAW_DATA, $iv);

        return json_decode($decrypted, true);
    }
}
