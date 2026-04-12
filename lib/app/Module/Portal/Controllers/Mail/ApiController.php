<?php

namespace App\Module\Portal\Controllers\Mail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Module\Portal\Entity\MailEntity;

class ApiController extends Controller
{
    protected $mail;

    public function __construct(MailEntity $mail)
    {
        $this->mail = $mail;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $data = $this->mail->all($request);
        return $this->respondSuccess($data);
    }

    public function detail($id)
    {
        $data = $this->mail->find($id);
        return $this->respondSuccess($data);
    }

    public function sendMail(Request $request)
    {
        $data = $this->mail->sendMail($request);
        return $this->respondSuccess($data);
    }
}