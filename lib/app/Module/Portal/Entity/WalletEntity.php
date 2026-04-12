<?php

namespace App\Module\Portal\Entity;

use App\Module\Portal\Jobs\SendMailVerifyOTPTransaction;
use App\Module\Portal\Model\MailConfig;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use App\Module\Portal\Repository\WalletRepository;
use App\Module\Portal\Permission\WalletPermission;
use App\Module\Portal\Repository\BillRepository;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Package\Exception\HttpException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

/**
 * @method uploadAndSaveFile($files, object $data_upload, $username)
 */
class WalletEntity extends AbstractEntity implements EntityInterface
{

  public const DAILY_LIMIT = 50000000; //Han muc 50 triệu

  public const URL_API_VIETQR = 'https://api.vietqr.io/v2/generate';
  /**
   * @var WalletRepository
   */
  protected $repository;
  protected $createValidator;
  protected $updateValidator;
  protected $errors;
  protected $mailConfig;
  protected $billRepository;
  protected $permission;
  /**
   * RegisterEntity constructor.
   * @param WalletRepository $repository
   */
  public function __construct(
    WalletRepository $repository,
    WalletPermission $permission
  ) {
    $this->repository = $repository;
    $this->permission = $permission;
    $this->mailConfig = MailConfig::where('type', 'system')->first();
    if (!$this->mailConfig) {
      throw new HttpException("Khong tim thay cau hinh mail !");
    }
    $this->billRepository = new BillRepository();
  }

  public function recharge($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $userLogin = auth()->guard('api')->user();
        $user_id = $userLogin->id;
        $wallet = $this->repository->find($user_id);
        if (!$wallet) {
          $this->repository->createNewWallet($user_id);
        }
        $dataCreateBill = [];
        $dataCreateBill['card_id'] = $request->card_id;
        $dataCreateBill['type'] = 'recharge';
        $dataCreateBill['transaction_type'] = 1;
        $dataCreateBill['total_amount'] = $request->amount;
        $dataCreateBill['methods'] = $request->methods;
        $bill = $this->billRepository->create($dataCreateBill);
        return $bill;
      });
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }

  public function withdrawal($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $user_id = Auth::guard('api')->user()->id;
        if (!isset($request->session_id)) {
          throw new HttpException("Giao dịch chưa được xác thực !");
        }

        $checkSessionVerified = $this->repository->checkSessionVerified($request->session_id);

        if (!$checkSessionVerified) {
          throw new HttpException("Giao dịch chưa được xác thực !");
        }
        $checkSessionVerified->delete();

        $card = $this->repository->findCard($request->card_id);
        if ($card) {
          $this->checkAvailabilityWallet(2, $user_id, $request->amount);
          //
          $wallet = $this->repository->find($user_id);
          if ($wallet) {
            $wallet->availability_amount -= $request->amount;
            $wallet->save();
          }
          //
          $dataCreateBill = [];
          $dataCreateBill['card_id'] = $request->card_id;
          $dataCreateBill['type'] = 'withdrawal';
          $dataCreateBill['transaction_type'] = 2;
          $dataCreateBill['total_amount'] = $request->amount;
          $dataCreateBill['methods'] = $request->methods;
          $bill = $this->billRepository->create($dataCreateBill);

          if ($bill) {
            return [
              'id' => $bill->id
            ];
          }
          return null;
        }
        return null;
      });
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }
  //
  public function checkAvailabilityWallet($typeTransaction, $user_id, $amount)
  {
    if ($typeTransaction == 2) {
      $messageInsufficient = "Số dư khả dụng không đủ để rút!";
      $wallet = $this->repository->find($user_id);
      if (!$wallet || $amount > $wallet->availability_amount) {
        throw new HttpException($messageInsufficient);
      }
    }
    return true;
  }

  public function createTransactionSession($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $user_id = Auth::guard('api')->user()->id;
        $getTransactionCompleteToday = $this->repository->getTransactionDoneToDay($user_id);
        if ($getTransactionCompleteToday > self::DAILY_LIMIT) {
          $messageLimited = "Số tiền rút đã vượt quá hạn mức " . number_format(self::DAILY_LIMIT) . "đ cho phép trong ngày của tài khoản, hiện tại không thể thực hiện thêm giao dịch !";
          throw new HttpException($messageLimited);
        }
        $OTP = substr(rand(), 0, 6);
        $user = Auth::guard('api')->user();
        $session = $this->repository->createTransactionSession();
        if ($session) {
          $arraySession = array(
            'session_id' => $session->id,
            'user_id' => $user->id,
            'otp_code' => $OTP,
            'expired' => now()->addMinutes(2),
            'created_at' => now()
          );
          $this->repository->createTransactionOTP($arraySession);

          SendMailVerifyOTPTransaction::dispatch($user, $OTP, $this->mailConfig);
          return ["session_id" => $session->id];
        }
        return false;
      });
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }

  public function resendOTP($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $OTP = substr(rand(), 0, 6);
        $user = Auth::guard('api')->user();
        $transactionOTP = $this->repository->findTransactionOTP($request->session_id);
        if ($transactionOTP) {

          $transactionOTP->otp_code = $OTP;
          $transactionOTP->expired = now()->addMinutes(2);
          $transactionOTP->updated_at = now();

          $transactionOTP->save();

          SendMailVerifyOTPTransaction::dispatch($user, $OTP, $this->mailConfig);
          return ["session_id" => $request->session_id];
        }
        throw new HttpException("Không tìm thấy giao dịch để gửi lại !");
      });
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }

  public function verifyOTP($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $user_id = Auth::guard('api')->user()->id;
        $session = $this->repository->findTransactionSession($request->session_id);

        if (!$request->otp) {
          throw new HttpException("Vui lòng nhập mã OTP !");
        }

        $getOTP = $this->repository->findTransactionOTP($session->id);

        if (Carbon::parse($getOTP->expired)->format('Y-m-d H:i:s') < now()->format('Y-m-d H:i:s')) {
          throw new HttpException("Mã OTP đã hết hạn !");
        }

        if ($getOTP->otp_code != $request->otp) {
          throw new HttpException("Mã OTP không chính xác !");
        }

        $session_id = $session->id;
        $session->delete();
        //

        $arrayTransactionVerified = array(
          'session_id' => $session_id,
          'created_at' => now()
        );
        $this->repository->createTransactionVerified($arrayTransactionVerified);

        return ['session_id' => $session_id];
      });
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }

  public function getQR($request)
  {
    try {
      return DB::transaction(function () use ($request) {
        $card = $this->repository->findCard($request->card_id);
        $amount = 0;
        if (isset($request->transaction_id)) {
          $transaction = $this->repository->findTransaction($request->transaction_id);
          $amount = $transaction->amount;
        } elseif (isset($request->amount)) {
          $amount = $request->amount;
        }

        $payload = array(
          "accountNo" => $card->account_number,
          "accountName" => $card->cardholder,
          "acqId" => $card->cardType ? $card->cardType->acqId : null,
          "amount" => $amount,
          "template" => "compact"
        );
        $response = json_decode($this->callAPICreateQRFromVietQR(self::URL_API_VIETQR, $payload), true);
        return $response;
      });
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }

  public function getCurrentBearerToken()
  {
    $authorizationHeader = request()->header('Authorization');
    if ($authorizationHeader && strpos($authorizationHeader, 'Bearer ') === 0) {
      $bearerToken = substr($authorizationHeader, 7);
      return $bearerToken;
    }
    return null;
  }

  public function callAPICreateTransaction($apiUrl, $bearerToken, $payload)
  {
    try {
      $client = new Client();
      $response = $client->post($apiUrl, [
        'headers' => [
          'Authorization' => 'Bearer ' . $bearerToken,
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
        'json' => $payload,
      ]);
      $result = $response->getBody()->getContents();
      return $result;
    } catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  public function callAPICreateQRFromVietQR($apiUrl, $payload)
  {
    try {
      $client = new Client();
      $response = $client->post($apiUrl, [
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
        'json' => $payload,
      ]);
      $result = $response->getBody()->getContents();
      return $result;
    } catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  public function getAmount()
  {
    $user_id =  Auth::guard('api')->user()->id;
    $wallet = $this->repository->find($user_id);
    if (!$wallet) {
      $wallet =  $this->repository->createNewWallet($user_id);;
    }
    return $wallet;
  }
}
