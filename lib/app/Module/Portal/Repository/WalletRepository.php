<?php

namespace App\Module\Portal\Repository;

use App\Jobs\SendMailJob;
use App\Models\Role;
use App\Module\Portal\Model\Business;
use App\Module\Portal\Model\Media;
use App\Module\Portal\Model\ModulePermission;
use App\Module\Portal\Model\Wallet\Card;
use App\Module\Portal\Model\Wallet\Transaction;
use App\Module\Portal\Model\Wallet\TransactionOTP;
use App\Module\Portal\Model\Wallet\TransactionSession;
use App\Module\Portal\Model\Wallet\TransactionVerified;
use App\Module\Portal\Model\Wallet\Wallet;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Package\Exception\DatabaseException;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Package\Exception\HttpException;

class WalletRepository extends BasicEntity implements RepositoryInterface
{

  public $table;

  public $primaryKey;

  public $fillable;

  public $hidden;

  public function __construct()
  {
    $user = new User();
    $this->table =  $user->getTable();
    $this->fillable =  $user->getFillable();
    $this->hidden =  $user->getHidden();
    $this->primaryKey =  $user->getKeyName();
    array_push($this->fillable, $this->primaryKey);
  }

  public function all($request)
  {
  }

  public function find($id)
  {
    return Wallet::where('user_id', $id)->first();
  }

  public function findCard($id)
  {
    $card = Card::with(['cardType'])->where('id', $id)->first();
    if (!$card) {
      throw new HttpException("Không tìm thấy phương thức thanh toán !");
    }
    return $card;
  }

  public function createNewWallet($user_id)
  {
    return Wallet::create([
      'user_id' => $user_id,
      'original' => 0,
      'marketing' => 0,
      'availability' => 0,
      'promotion' => 0,
      'created_at' => now()
    ]);
  }

  public function createTransaction(array $data)
  {
    return Transaction::create($data);
  }

  public function findTransaction($id)
  {
    $transaction = Transaction::where('id', $id)->first();
    if (!$transaction) {
      throw new HttpException("Không tìm thaawsy giao dịch !");
    }
    return $transaction;
  }

  public function getTransactionDoneToDay($user_id)
  {
    return Transaction::where('created_by', $user_id)
      ->whereDate('transaction_date', now()->format('Y-m-d'))
      ->where('transaction_type', 2)
      ->whereIn('status', [1, 2, 3, 4])
      ->sum('amount');
  }

  public function findTransactionSession($id)
  {
    $session = TransactionSession::where('id', $id)->first();

    if (!$session) {
      throw new HttpException("Không tìm thấy phiên giao dịch !");
    }

    return $session;
  }

  public function createTransactionSession()
  {
    return TransactionSession::create(['time' => time()]);
  }

  public function createTransactionOTP(array $data)
  {
    return  TransactionOTP::create($data);
  }

  public function findTransactionOTP($id)
  {
    $otp = TransactionOTP::where('session_id', $id)->first();

    if (!$otp) {
      throw new HttpException("Không tìm thấy phiên xác thực !");
    }
    return $otp;
  }

  public function createTransactionVerified(array $data)
  {
    return TransactionVerified::create($data);
  }

  public function checkSessionVerified($session_id)
  {
    return TransactionVerified::where('session_id', $session_id)->first();
  }

  public function create($data)
  {
  }

  public function createManyOfRow($data)
  {
    try {
      $model = $this->CreateManyRow($data);

      return $model;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function update($data)
  {
  }

  public function allTrash($pagin = null)
  {
    return  $this->findAllTrash($pagin);
  }

  public function trash($id, $trash = true)
  {
    try {
      $this->id = $id;
      $model = $this->TrashOrRecover($id, $trash);

      return $model;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function delete($id)
  {
    try {
      $user = User::where('id', $id)->first();

      if (!$user) {
        throw new HttpException("Không tìm thấy dữ liệu !");
      }
      $user->delete();

      return   $user;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }
}
