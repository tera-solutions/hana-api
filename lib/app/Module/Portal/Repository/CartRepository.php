<?php

namespace App\Module\Portal\Repository;

use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Exception;
use Package\Exception\DatabaseException;
use App\Models\Media;
use App\Module\Portal\Helpers\PaymentCart;
use App\Module\Portal\Model\Business;
use App\Module\Portal\Model\Cart;
use App\Module\Portal\Model\PackageService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Package\Exception\HttpException;
use App\Module\Portal\Repository\ServiceRepository;

class CartRepository extends BasicEntity implements RepositoryInterface
{
  public $table;
  public $primaryKey;
  public $fillable;
  public $hidden;
  public $paymentCart;
  public $serviceRepository;
  public function __construct()
  {
    $cart = new Cart();
    $this->table = $cart->getTable();
    $this->fillable = $cart->getFillable();
    $this->hidden =  $cart->getHidden();
    $this->primaryKey =  $cart->getKeyName();
    $this->paymentCart = new PaymentCart();
    $this->serviceRepository = new ServiceRepository();
    array_push($this->fillable, $this->primaryKey);
  }
  public function all($request)
  {
    $user_id = Auth::guard('api')->user()->id;
    $cart = Cart::with(['package.service'])
      ->where('user_id', $user_id)
      ->select([
        "*",
      ]);
    $sort_field = "created_at";
    $sort_des = "desc";
    if (isset($request->order_field) && $request->order_field) {
      $sort_field = $request->order_field;
    }
    if (isset($request->order_by) && $request->order_by) {
      $sort_des = $request->order_by;
    }
    $cart->orderBy($sort_field, $sort_des);
    $data = $cart->paginate($request->limit);
    return $data;
  }
  public function find($id)
  {
    $user_id = Auth::guard('api')->user()->id;
    $data = Cart::select([
      "id",
    ])->where('user_id', $user_id)->find($id);
    return $data;
  }
  public function create($data)
  {
    try {
      return DB::transaction(function () use ($data) {
        $user_id = Auth::guard('api')->user()->id;
        $checkPackage = Cart::with(['package'])
          ->where('user_id', $user_id)
          ->where('package_id', $data['package_id'])
          ->first();
        $package = PackageService::with(['service'])
          ->where('id', $data['package_id'])
          ->firstOrFail();
        if (!$package->service) {
          throw new HttpException("Không tìm thấy gói dịch vụ !");
        }
        //
        $getItemsCart = Cart::with(['package.service:id,name'])
          ->where('user_id', $user_id)
          ->get();
        foreach ($getItemsCart as $item) {
          if ($item->package) {
            if ($item->package->service) {
              if (
                $item->package->service->id == $package->service->id
                &&
                $item->package->id != $package->id
              ) {
                $item->delete();
              }
            }
          }
        }
        //
        if ($checkPackage) {
          $checkPackage->quantity++;
          $checkPackage->total_time += $checkPackage->package->time;
          $checkPackage->total_amount = $checkPackage->quantity * $checkPackage->package->price;
          $checkPackage->save();
        } else {
          $checkPackage =   Cart::create([
            'user_id' => $user_id,
            'package_id' => $data['package_id'],
            'quantity' => 1,
            'amount' => $package->price,
            'total_amount' => $package->price * 1,
            'total_time' => $package->time,
            'created_at' => now()
          ]);
        }
        return $checkPackage;
      });
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
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
        $getItem = Cart::with(['package'])->where('id', $data['id'])->first();
        if ($getItem) {
          $getItem->quantity = $data['quantity'];
          $getItem->total_time = $data['quantity'] * $getItem->package->time;
          $getItem->total_amount = $data['quantity'] * $getItem->package->price;
          $getItem->save();
        }
        return  $getItem;
      });
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }
  public function replace($data)
  {
    try {
      return DB::transaction(function () use ($data) {
        $getItem = Cart::where('id', $data['id'])->first();
        $package = PackageService::where('id', $data['package_id'])->first();
        if ($getItem && $package) {
          $getItem->package_id = $package->id;
          $getItem->quantity = 1;
          $getItem->amount = $package->price;
          $getItem->total_time = $package->time;
          $getItem->total_amount = $package->price *  $getItem->quantity;
          $getItem->save();
        }
        return $getItem;
      });
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }
  public function getPaymentCart($request)
  {
    $isPlusStartUp = false;
    $user_id = Auth::guard('api')->user()->id;
    if (isset($request['items'])) {
      $items  = collect(array_map(function ($item) {
        $package = PackageService::where('id', $item['package_id'])->first();
        if ($package) {
          $item["total_time"] = $package->time * $item['quantity'];
          $item["amount"] = $package->price;
          $item["total_amount"] = $package->price * $item['quantity'];
        }
        return $item;
      }, $request['items']));
    } elseif (isset($request['item_selected'])) {
      $isPlusStartUp = true;
      $items = Cart::with(['package.service'])->where('user_id', $user_id)->whereIn('id', $request['item_selected'])->get();
    }

    if (isset($request['bill_active'])) {
      if ($request['bill_active'] == 1) {
        $isPlusStartUp = true;
      }
    }

    $items = collect(array_map(function ($item) {
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
      }
      return $item;
    }, collect($items)->toArray()));
    return $this
      ->paymentCart
      ->setItems($items)
      ->getInformationPayment($isPlusStartUp);
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
      $check = Cart::where('id', $id)->first();
      if (!$check) {
        throw new HttpException("Không tìm thấy sản phẩm trong giỏ hàng !");
      }
      $check->delete();
      return $check;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }
  public function getCountCart()
  {
    $user_id = Auth::guard('api')->user()->id;
    return Cart::where('user_id', $user_id)->count();
  }
}
