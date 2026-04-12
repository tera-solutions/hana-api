<?php

namespace App\Module\Portal\Repository;

use App\Helpers\Task;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Exception;
use Package\Exception\DatabaseException;
use App\Models\Media;
use App\Module\Portal\Constants\CommonConstants;
use App\Module\Portal\Helpers\PaymentCart;
use App\Module\Portal\Helpers\SyncPermissionRole;
use App\Module\Portal\Model\AccountingVoucher;
use App\Module\Portal\Model\AccountingVoucherExplain;
use App\Module\Portal\Model\Bill;
use App\Module\Portal\Model\BillAccounting;
use App\Module\Portal\Model\BillItem;
use App\Module\Portal\Model\BillTransaction;
use App\Module\Portal\Model\Business;
use App\Module\Portal\Model\BusinessService;
use App\Module\Portal\Model\Cart;
use App\Module\Portal\Model\PackageService;
use App\Module\Portal\Model\Wallet\Transaction;
use App\Module\Portal\Model\Wallet\Wallet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Package\Exception\HttpException;
use App\Module\Portal\Repository\ServiceRepository;

class BillRepository extends BasicEntity implements RepositoryInterface
{

  public $table;

  public $primaryKey;

  public $fillable;

  public $hidden;

  protected $roleHelper;

  protected $cartRepository;

  public $serviceRepository;

  public $userId;

  public function __construct()
  {
    $bill = new Bill();
    $this->table = $bill->getTable();
    $this->fillable = $bill->getFillable();
    $this->hidden =  $bill->getHidden();
    $this->primaryKey =  $bill->getKeyName();
    $this->roleHelper = new SyncPermissionRole();
    $this->cartRepository = new CartRepository();
    $this->serviceRepository = new ServiceRepository();
    $this->userId = Auth::guard('api')->user()->id;
    array_push($this->fillable, $this->primaryKey);
  }

  public function all($request)
  {
    $user_id = $this->userId;
    $bill = Bill::with(['customer:id,avatar', 'items'])->select([
      "*",
    ])->where('created_by', $user_id);

    if (isset($request->keyword)) {
      $bill->where('code', 'LIKE', "%$request->keyword%");
    }

    if (isset($request->status) && $request->status) {
      $bill->where('status', $request->status);
    }

    if (isset($request->transaction_type) && $request->transaction_type) {
      $bill->where('transaction_type', $request->transaction_type);
    }

    if (isset($request->methods) && $request->methods) {
      $bill->where('methods', $request->methods);
    }

    if (isset($request->transaction_date) && $request->transaction_date) {
      $transactionDate = explode(",", $request->transaction_date);
      list($fromDate, $toDate) = $transactionDate;
      $fromDate = Carbon::createFromFormat("Y-m-d", $fromDate);
      $toDate = Carbon::createFromFormat("Y-m-d", $toDate);
      $bill->whereDate('transaction_date', '>=', $fromDate);
      $bill->whereDate('transaction_date', '<=', $toDate);
    }

    $sort_field = "transaction_date";
    $sort_des = "desc";

    if (isset($request->order_field) && $request->order_field) {
      $sort_field = $request->order_field;
    }

    if (isset($request->order_by) && $request->order_by) {
      $sort_des = $request->order_by;
    }

    $bill->orderBy($sort_field, $sort_des);

    $data = $bill->paginate($request->limit);
    return $data;
  }
  //
  public function find($id)
  {
    $user_id = $this->userId;
    $data = Bill::with(['customer:id,avatar', 'items.package.service', 'transactions.transaction'])->select([
      "*"
    ])->where('created_by', $user_id)->find($id);
    return $data;
  }

  private function isCanUsePackage($business_id)
  {
    $status = Business::where('id', $business_id)->pluck('status')->first();

    if ($status === 'cancelled') {
      throw new HttpException("Doanh nghiệp này đã bị hủy kích hoạt !");
    }
  }

  public function create($data)
  {
    try {
      return DB::transaction(function () use ($data) {
        $isBuy = true;
        if (in_array($data['type'], ['recharge', 'withdrawal'])) {
          $isBuy = false;
        }
        $userLogin = auth()->guard('api')->user();
        $user_id = $userLogin->id;
        $business_id = $userLogin->business_id;
        //
        if ($isBuy) {
          if (($userLogin->business_id != null) && ($userLogin->type == 'owner' || $userLogin->type == 'member')) {
            $this->isCanUsePackage($business_id);
          }
        }
        //
        $data['created_by'] = $user_id;
        $data['business_id'] = $business_id;
        $data['transaction_date'] = now()->format('Y-m-d H:i:s');
        $data['status'] = 'unpaid';
        $data['code'] = 'HD' . time();
        //
        $data['customer_name'] = $userLogin->full_name;
        $data['customer_email'] = $userLogin->email;
        $data['customer_phone'] = $userLogin->phone;
        //
        if ($isBuy) {
          if (isset($data['items'])) {
            $itemsSelectedCart['items'] = array_map(function ($item) {
              $package = PackageService::with(['service'])->where('id', $item['package_id'])->first();
              if ($package) {
                $item["total_time"] = $package->time * $item['quantity'];
                $item["amount"] = $package->price;
                $item["total_amount"] = $package->price * $item['quantity'];
                $item['package'] =  $package->toArray();
                $item['package']['service'] =  $package->service ? $package->service()->first()->toArray() : null;
                return $item;
              }
            }, $data['items']);
          } else {
            $itemsSelectedCart['item_selected'] = $data['selected_items'];
          }
        }

        if (isset($itemsSelectedCart['items'])) {
          $itemsSelectedCart['items'] = array_map(function ($item) {
            if (isset($item['old_id'])) {
              $objectRequest = new Request();
              $objectRequest->merge(
                [
                  'current_id' => $item['old_id'],
                  'upgrade_id' => $item['package_id'],
                  'quantity' => $item['quantity'],
                ]
              );
              $getOldPackage = $this->serviceRepository->calculateOldPackage($objectRequest);
              $item['total_amount'] = $getOldPackage['total_amount'];
              $item['old_package'] = $getOldPackage['old_package'];
            }
            $item['price'] = $item['amount'];
            return $item;
          }, $itemsSelectedCart['items']);
        }
        $cartInformation = [];
        if ($isBuy) {
          if (empty($itemsSelectedCart['item_selected']) && empty($itemsSelectedCart['items'])) {
            throw new HttpException("Không có sản phẩm nào thanh toán !");
          }
          //
          if ($data['type'] === 'active') {
            $itemsSelectedCart['bill_active'] = 1;
          }
          //
          $cartInformation = $this->cartRepository->getPaymentCart($itemsSelectedCart);
        }
        $data['total_amount_product'] = ($isBuy) ? $cartInformation['total_amount_product'] : $data['total_amount'];
        $data['discount'] = ($isBuy) ? $cartInformation['discount'] : 0;
        $data['vat_tax'] = ($isBuy) ?  $cartInformation['vat_tax'] : 0;
        $data['total_amount'] = ($isBuy) ?  $cartInformation['total_amount'] : $data['total_amount'];
        $data['transaction_type'] = ($isBuy) ? 3 : $data['transaction_type'];
        $data['start_up_fee'] = $cartInformation['start_up_fee'] ?? 0;
        //
        $methodWallet = null;
        if ($isBuy) {
          if (isset($data['methods'])) {
            unset($data['methods']);
          }
        } else {
          $methodWallet = @$data['methods'];
          $data['methods'] = 1;
        }
        $bill = Bill::create($data);
        if (!$bill) {
          throw new HttpException("Không thể thêm hóa đơn !");
        }
        if ($isBuy) {
          if (!empty($itemsSelectedCart['items'])) {
            $this->addItems(
              $bill,
              $itemsSelectedCart['items']
            );
          }
          $this->resetCart($user_id, BillItem::where('bill_id', $bill->id)->pluck('package_id')->toArray());
        }
        $methodsTransaction = ($isBuy) ? null : $methodWallet;
        $transaction = $this->createHistoryTransaction($bill, $user_id, $methodsTransaction, 3);
        $bill->transaction_id = $transaction ? $transaction->id : null;
        return  $bill;
      });
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function addItems($bill, $items = [])
  {
    $dataInsert = [];
    if ($bill) {
      foreach ($items as $key => $value) {
        $package = PackageService::with(['service:id,name'])->where('id', $value['package_id'])->first();
        if ($package) {
          $dataInsert[] = [
            'bill_id' => $bill->id,
            'package_name' => $package->name,
            'service_name' => $package->service ? $package->service->name : null,
            'quantity' => $value['quantity'],
            'total_time' => $package->time * $value['quantity'],
            'old_package' => @$value['old_package'],
            'price' =>  $package->price,
            'total_amount' => isset($value['old_id']) ? $value['total_amount'] :  $package->price * $value['quantity'],
            'package_id' => $value['package_id'],
            'created_at' => now(),
            'users_count' => $package->quantity_user,
            'size' => $package->quantity_capacity,
            'orders' => $package->quantity_order,
            'time' => $package->time,
          ];
        }
      }
      if (!empty($dataInsert)) {
        BillItem::insert($dataInsert);
      }
    }
  }

  public function getItemsInCart($ids)
  {
    return Cart::whereIn('id', $ids)->get();
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
    try {
      return DB::transaction(function () use ($data) {

        return true;
      });
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
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
      $check = Cart::where('id', $id)->firstOrFail();
      $check->delete();
      return true;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function transfer($request)
  {
    $transferSuccess = true;
    //
    $bill = Bill::query();

    if (isset($request->bill_id)) {
      $bill->where('id', $request->bill_id);
    }

    if (isset($request->bill_code)) {
      $bill->where('code', $request->bill_code);
    }

    $bill = $bill->first();
    if ($bill && $transferSuccess) {
      $requestObject = new Request();
      $requestObject->merge(
        [
          'bill_id' => $bill->id,
          'methods' => 1
        ]
      );
      $this->pay($requestObject);
      return true;
    }

    return false;
  }

  public function pay($request)
  {
    $methods = $request->input('methods', 3);
    $user_id = $this->userId;
    $bill = Bill::with(['items.package.service'])->where('id', $request->bill_id)->first();
    if (!$bill) {
      throw new HttpException("Không tìm thấy hóa đơn !");
    }

    if (now()->format('Y-m-d') > Carbon::parse($bill->transaction_date)->format('Y-m-d')) {
      throw new HttpException("Hóa đơn đã quá hạn thanh toán !");
    }

    if ($bill->status != 'unpaid') {
      throw new HttpException("Trạng thái hiện tại không hợp lệ !");
    }

    if ($bill->allow_pay == false) {
      throw new HttpException("Hóa đơn này không thể thanh toán !");
    }
    $idTransactionRecent = BillTransaction::where('bill_id', $bill->id)
      ->orderBy('id', 'desc')
      ->pluck('transaction_id')
      ->first();
    $historyTransaction = Transaction::where('id', $idTransactionRecent)
      ->first();
    if ($historyTransaction->status != 3) {
      $historyTransaction = $this->createHistoryTransaction($bill, $user_id, $historyTransaction->methods, 3);
    }
    if ($methods == 3) {
      $messageNotEnoughMoney = 'Số dư trong ví không đủ !';
      $wallet = Wallet::where('user_id', $user_id)->first();
      if (!$wallet) {
        $this->changeStatusTransaction($historyTransaction, 2, null);
        throw new HttpException($messageNotEnoughMoney);
      }
      if ($wallet->availability_amount < $bill->total_amount) {
        $this->changeStatusTransaction($historyTransaction, 2, null);
        throw new HttpException($messageNotEnoughMoney);
      }
    }
    //
    $this->setStatusBill($bill, 'paid');
    if ($methods == 3) {
      $this->setWallet(
        $wallet,
        $bill->total_amount
      );
    }
    //
    $this->changeStatusTransaction($historyTransaction, 1, $methods);
    //
    $setup = $this->processingSetup($bill);
    if ($setup) {
      $this->setStatusBill($bill, 'complete');
      //
      if ($methods == 1) {
        $this->createReceipt(
          $bill,
          $user_id,
          $bill->total_amount
        );
      }

      $bill->methods = $methods;
      $bill->save();
    }

    return $historyTransaction;
    //
  }

  public function suggestCodeReceipt()
  {
    $count = Task::setAndGetReferenceCountModuleAdmin("accounting_voucher_receipt") + 1;
    $code = "PT" . $count;
    return $code;
  }

  public function createReceipt($bill, $user_id, $totalAmount)
  {
    $dataReceipt = [
      'code' => $this->suggestCodeReceipt(),
      'ballot_type' => 'collect_money_customer',
      'pay_method_type' => 'transfer',
      'date' => Carbon::createFromFormat('Y-m-d H:i:s', $bill->transaction_date)->format('Y-m-d H:i:s'),
      'accounting_date' => Carbon::createFromFormat('Y-m-d H:i:s', $bill->transaction_date)->format('Y-m-d H:i:s'),
      'accounted_business_result' => 1,
      'submitter_id' => $bill->created_by,
      'object_type' => 'receipt',
      'created_by' => $user_id,
      'created_at' => now()
    ];
    $receipt =  AccountingVoucher::create($dataReceipt);
    if ($receipt) {
      $dataExplainVoucher = [];
      //
      $dataExplainVoucher[] = [
        'voucher_id' => $receipt->id,
        'explain' => 'Tổng giá trị hóa đơn mua hàng',
        'amount' => $totalAmount,
        'created_at' => now()
      ];

      AccountingVoucherExplain::insert($dataExplainVoucher);
      //
      BillAccounting::create([
        'voucher_id' => $receipt->id,
        'bill_id' => $bill->id
      ]);
    }
  }

  public function createHistoryTransaction($bill, $user_id, $methods, $status)
  {
    //
    $arrayTransaction = array();
    if ($bill->card_id) {
      $arrayTransaction['card_id']  = $bill->card_id;
    }
    $arrayTransaction['transaction_code'] = 'GD' . strtotime(now()->format('Y-m-d H:i:s'));
    $arrayTransaction['transaction_date'] = now()->format('Y-m-d H:i:s');
    $arrayTransaction['status']  = $status;
    $arrayTransaction['transaction_type']  = $bill->transaction_type;
    $arrayTransaction['amount']  = $bill->total_amount;
    $arrayTransaction['created_by']  = $user_id;
    $arrayTransaction['methods']  = $methods;
    $transaction =  $this->createTransaction($arrayTransaction);
    $this->createReferenceBillTransaction([
      'bill_id' => $bill->id,
      'transaction_id' => $transaction->id,
    ]);
    return $transaction;
  }


  public function createReferenceBillTransaction(array $array)
  {
    BillTransaction::create($array);
  }

  public function changeStatusTransaction($transaction, $status = 1, $methods)
  {
    if ($transaction) {
      $transaction->methods = $methods;
      $transaction->status = $status;
      $transaction->save();
    }
  }

  public function setWallet($wallet, $total): void
  {
    $wallet->original_amount -= $total;
    $wallet->availability_amount -= $total;
    $wallet->save();
  }

  public function resetCart($user_id, $itemsBought): void
  {
    Cart::where('user_id', $user_id)->whereIn('package_id', $itemsBought)->delete();
  }

  private function processingSetup($bill)
  {
    $business_id = $bill->business_id;
    $business = Business::where('id', $business_id)->first();
    if (!$business) {
      throw new HttpException("Doanh nghiệp mua không tồn tại !");
    }
    $this->setStatusBill($bill, 'in_process');
    try {
      if (!empty($bill->items)) {
        $this->loopItems(
          $bill->type,
          $bill->items,
          $business->id
        );
      }
      $this->roleHelper->syncPermissionsBusiness($business->id, [], true);
      return true;
    } catch (\Exception $e) {
      $this->setStatusBill($bill, 'fail');
    }
  }

  public function loopItems($functionType, $items, $business_id)
  {
    foreach ($items as $item) {
      if (!$item->package) {
        throw new Exception();
      }
      if (!$item->package->service) {
        throw new Exception();
      }
      $input = [];
      switch ($functionType) {
        case 'upgrade':
          $this->setStatusFailForBillSamePackage($item);
          $input = array(
            'total_time' => $item->total_time,
            'business_id' => $business_id,
            'service_id' => $item->package->service->id,
            'package_id' => $item->package->id,
            'service_type' => $item->package->service->type,
          );
          break;
        case 'extend':
          $packageExtend = BusinessService::with(['business', 'package', 'service'])
            ->where('business_id', $business_id)
            ->where('service_id', $item->package->service->id)
            ->where('package_id', $item->package->id)
            ->first();
          if ($packageExtend) {
            $input = array(
              'total_time' => $item->total_time,
              'package_extend' => $packageExtend
            );
          }
          break;
        case 'restart':
          $this->setStatusFailForBillSamePackage($item);
          $packageExtend = BusinessService::with(['business', 'package', 'service'])
            ->where('business_id', $business_id)
            ->where('service_id', $item->package->service->id)
            ->where('package_id', $item->package->id)
            ->first();
          if ($packageExtend) {
            $input = array(
              'package_extend' => $packageExtend,
              'total_time' => $item->total_time,
            );
          }
          break;
        case 'active':
          //
          $this->setStatusFailForBillSamePackage($item);
          //
          $input = array(
            'total_time' => $item->total_time,
            'business_id' => $business_id,
            'service_id' => $item->package->service->id,
            'package_id' => $item->package->id,
            'service_type' => $item->package->service->type,
          );
          break;
      }
      call_user_func_array(array($this, $functionType), [$input]);
    }
  }

  private function setStatusFailForBillSamePackage($item)
  {
    $business_id = null;
    $idFailed = [];
    $today = now()->format('Y-m-d');
    //
    $bill_id = $item->bill_id;
    //
    $currentBill = Bill::find($bill_id);
    if ($currentBill) {
      $business_id = $currentBill->business_id;
      if ($item->package) {
        if ($item->package->service) {
          $service_id = $item->package->service->id;
          $billRelation = Bill::with(['items.package'])
            ->whereDate('transaction_date', $today)
            ->where('status', 'unpaid')
            ->where('id', '!=', $bill_id)
            ->where('type', $currentBill->type)
            ->where('business_id', $business_id)
            ->get();
          foreach ($billRelation as $bill) {
            if (!empty($bill->items)) {
              $items = $bill->items;
              foreach ($items as $itemPackage) {
                if ($itemPackage->package) {
                  if ($itemPackage->package->service_id == $service_id) {
                    $idFailed[] = $bill->id;
                  }
                }
              }
            }
          }
        }
      }
    }
    if (!empty($idFailed)) {
      Bill::whereIn('id', $idFailed)
        ->update(['status' => 'fail']);

      //
      foreach ($idFailed as $key => $value) {
        $transactionOfBill = BillTransaction::where('bill_id', $value)->pluck('transaction_id')->toArray();
        if (!empty($transactionOfBill)) {
          $idRecent = max($transactionOfBill);
          Transaction::where('id', $idRecent)->where('status', 3)->update(['status' => 2]);
        }
      }
    }
  }

  public function createTransaction(array $data)
  {
    return Transaction::create($data);
  }

  private function setStatusBill($bill, $status)
  {
    Log::info($status, [
      CommonConstants::STATUS_BILL[$status] ?? null,
    ]);
    $bill->status = $status;
    $bill->save();
  }

  /**
   * Handle deactivation of trial or official service based on current service.
   *
   * @param \App\Modelss\BusinessService $current
   * @param int $currentID
   * @throws HttpException
   */
  private function handleServicesUsing($current, $currentID = null)
  {
    if (!$current || empty($current->service)) {
      throw new HttpException("Gói hiện tại đang bị sai dữ liệu !");
    }
    $currentID = !is_null($currentID) ? $currentID : $current->id;
    if ($current->service->type === 'trial') {
      $checkOfficialIsActive = BusinessService::where('business_id', $current->business_id)
        ->where('service_type', 'official')
        ->where('status', 'is_active')
        ->count();

      if ($checkOfficialIsActive > 0) {
        throw new HttpException("Không kích hoạt gói dùng thử khi đang có gói chính thức hoạt động !");
      }

      BusinessService::where('business_id', $current->business_id)
        ->where('service_type', 'trial')
        ->where('service_id', '!=', $current->service_id)
        ->where('status', 'is_active')
        ->update(['status' => 'finished']);

      BusinessService::where('business_id', $current->business_id)
        ->where('service_id', $current->service_id)
        ->where('service_type', 'trial')
        ->where('id', '!=', $currentID)
        ->delete();
    } elseif ($current->service->type === 'official') {

      BusinessService::where('business_id', $current->business_id)
        ->where('service_type', 'trial')
        ->where('status', 'is_active')
        ->update(['status' => 'finished']);

      BusinessService::where('business_id', $current->business_id)
        ->where('service_id', $current->service_id)
        ->where('service_type', 'official')
        ->where('id', '!=', $currentID)
        ->delete();
    }
  }

  public function active($input)
  {
    try {
      if (!empty($input)) {
        $dateActive = now()->format('Y-m-d');
        $dateExpired = now()->addMonths($input['total_time'])->format('Y-m-d');
        $time = $input['total_time'];
        $status = 'is_active';
        $current =   BusinessService::updateOrCreate([
          'business_id' => $input['business_id'],
          'package_id' => $input['package_id'],
          'service_id' => $input['service_id']
        ], [
          'business_id' => $input['business_id'],
          'package_id' => $input['package_id'],
          'service_id' => $input['service_id'],
          'date_active' => $dateActive,
          'date_expired' => $dateExpired,
          'time' =>  $time,
          'status' => $status,
          'service_type' => $input['service_type']
        ]);
        $this->handleServicesUsing($current);
        return true;
      }
      return false;
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }

  public function upgrade($input)
  {
    try {
      if (!empty($input)) {
        $dateActive = now()->format('Y-m-d');
        $dateExpired = now()->addMonths($input['total_time'])->format('Y-m-d');
        $time = $input['total_time'];
        $status = 'is_active';
        $current =   BusinessService::updateOrCreate([
          'business_id' => $input['business_id'],
          'package_id' => $input['package_id'],
          'service_id' => $input['service_id']
        ], [
          'business_id' => $input['business_id'],
          'package_id' => $input['package_id'],
          'service_id' => $input['service_id'],
          'date_active' => $dateActive,
          'date_expired' => $dateExpired,
          'time' =>  $time,
          'status' => $status,
          'service_type' => $input['service_type']
        ]);
        $this->handleServicesUsing($current);
        //
        return true;
      }
      return false;
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }

  public function extend($input)
  {
    try {
      $current = @$input['package_extend'];
      if ($current) {
        $this->handleServicesUsing(
          $current
        );
        if ($current->status === 'is_active') {
          $dateExpired = Carbon::parse($current->date_expired)->addMonths($input['total_time']);

          $current->date_expired = $dateExpired->format("Y-m-d");
          $current->time += $input['total_time'];
        } else {
          $current->date_active = now()->format('Y-m-d');
          $current->date_expired = now()->addMonths($input['total_time'])->format('Y-m-d');
          $current->time = $input['total_time'];
        }
        $current->status = 'is_active';
        $current->save();
        //
        return true;
      }
      return false;
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }

  public function restart($input)
  {
    try {
      return DB::transaction(function () use ($input) {
        $current = @$input['package_extend'];
        if ($current) {
          $status = 'is_active';
          $this->handleServicesUsing(
            $current
          );
          $current->time = $input['total_time'];
          $current->date_active = now()->format('Y-m-d');
          $current->date_expired = now()->addMonths($input['total_time'])->format('Y-m-d');
          $current->status = $status;
          $current->save();

          return true;
        }
        return false;
      });
    } catch (HttpException $e) {
      throw new HttpException($e->getMessage());
    }
  }
}
