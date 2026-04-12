<?php

namespace App\Module\Portal\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Module\Portal\Entity\ServiceEntity;
use App\Module\Portal\Entity\WalletEntity;
use App\Module\Portal\Jobs\OTPTransaction;
use App\Module\Portal\Jobs\SendMailVerifyOTPTransaction;
use App\Module\Portal\Model\MailConfig;
use App\Module\Portal\Model\Wallet\Card;
use App\Module\Portal\Model\Wallet\OTP;
use App\Module\Portal\Model\Wallet\Transaction;
use App\Module\Portal\Model\Wallet\TransactionOTP;
use App\Module\Portal\Model\Wallet\TransactionSession;
use App\Module\Portal\Model\Wallet\TransactionVerified;
use App\Module\Portal\Model\Wallet\Wallet;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Exception\HttpException;

class ApiController extends Controller
{
  public $entity;
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function __construct(WalletEntity $entity)
  {
    $this->entity = $entity;
  }

  public function recharge(Request $request)
  {
    $result =  $this->entity->recharge($request);
    return $this->respondSuccess($result);
  }

  public function withdrawal(Request $request)
  {
    $result =  $this->entity->withdrawal($request);
    return $this->respondSuccess($result);
  }


  public function createTransactionSession(Request $request)
  {
    $result = $this->entity->createTransactionSession($request);
    return $this->respondSuccess($result);
  }

  public function resendOTP(Request $request)
  {
    $result = $this->entity->resendOTP($request);
    return $this->respondSuccess($result);
  }

  public function verifyOTP(Request $request)
  {
    $result = $this->entity->verifyOTP($request);
    return $this->respondSuccess($result);
  }

  public function getQR(Request $request)
  {
    $result = $this->entity->getQR($request);
    return $this->respondSuccess($result);
  }


  public function getAmount()
  {
    $result = $this->entity->getAmount();
    return $this->respondSuccess($result);
  }
}
