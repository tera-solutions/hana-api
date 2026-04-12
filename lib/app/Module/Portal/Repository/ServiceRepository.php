<?php

namespace App\Module\Portal\Repository;

use App\Models\Role;
use App\Models\RolePermission;
use App\Module\Portal\Model\Business;
use App\Module\Portal\Model\BusinessService;
use App\Module\Portal\Model\GroupPageControl;
use Illuminate\Support\Facades\DB;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Exception;
use App\Module\Portal\Model\Module;
use App\Module\Portal\Model\ModulePermission;
use App\Module\Portal\Model\PackageService;
use App\Module\Portal\Model\RolePermission as ModelRolePermission;
use App\Module\Portal\Model\Service;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Package\Exception\DatabaseException;
use Package\Exception\HttpException;

class ServiceRepository extends BasicEntity implements RepositoryInterface
{

  public $table;

  public $primaryKey;

  public $fillable;

  public $hidden;

  public function __construct()
  {
    $module = new Module();
    $this->table = $module->getTable();
    $this->fillable = $module->getFillable();
    $this->hidden = $module->getHidden();
    $this->primaryKey = $module->getKeyName();
    array_push($this->fillable, $this->primaryKey);
  }

  public function all($request)
  {
    $limit = 10;
    $business = Auth::guard('api')->user()->business_id;
    if (!$business) {
      throw new HttpException("Bạn chưa phải là chủ sở hữu hoặc thành viên của một doanh nghiệp !");
    }
    $comment = BusinessService::with(['service.packages:id,service_id,price,status', 'package'])
      ->where('business_id',  $business)
      ->select(["*"])
      ->whereIn('status', ['is_active', 'expired', 'finished']);

    $sort_field = "created_at";
    $sort_des = "desc";

    if (isset($request->keyword)) {
      $keyword = $request->keyword;
      $keywordInvalid = addcslashes($keyword, substr($keyword, 0, 1));
      $comment->whereHas('service', function ($query) use ($keywordInvalid) {
        $query->where('name', 'LIKE', "%$keywordInvalid%");
      });
    }

    if (isset($request->order_field) && $request->order_field) {
      $sort_field = $request->order_field;
    }

    if (isset($request->order_by) && $request->order_by) {
      $sort_des = $request->order_by;
    }

    if (isset($request->limit) && $request->limit) {
      $limit = $request->limit;
    }

    $comment->orderBy($sort_field, $sort_des);

    $data = $comment->paginate($limit);
    $data->map(function ($item) {
      $arrayPricesBigger = array_filter(collect($item->service->packages)
        ->toArray(), function ($itemFilter) use ($item) {
        return $itemFilter['price'] > $item->package->price && $itemFilter['status'] == 1;
      });
      $item->is_upgrade = empty($arrayPricesBigger) ? 0 : 1;
    });
    return $data;
  }

  public function listAvailability($request)
  {
    $business = Auth::guard('api')->user()->business_id;
    if (!$business) {
      throw new HttpException("Bạn chưa phải là chủ sở hữu hoặc thành viên của một doanh nghiệp !");
    }
    $services = Service::with([
      'packages' => function ($query) {
        $query->where('status', 1);
      }
    ])
      ->whereHas('packages', function ($query) {
        $query->where('status', 1);
      })
      ->whereDoesntHave('businesses', function ($query) use ($business) {
        $query->where('business_id', $business);
      });

    if (isset($request->keyword)) {
      $keyword = $request->keyword;
      $keywordInvalid = addcslashes($keyword, substr($keyword, 0, 1));
      $services->where('name', 'LIKE', "%$keywordInvalid%");
    }

    $records = $services
      ->where('status', 1)
      ->where('type', 'official')
      ->orderBy('created_at', 'desc')
      ->get();

    $records->map(function ($service) {
      $minPrice = null;
      $idPackageMinPrice = null;
      if ($service->packages->isNotEmpty()) {
        $sortedPackages = $service->packages->sortBy('price');
        $minPackage = $sortedPackages->first();
        $minPrice = $minPackage->price;
        $idPackageMinPrice = $minPackage->id;
      }
      $service['min_price'] = $minPrice;
      $service['package_min_id'] = $idPackageMinPrice;
      return $service;
    });

    $servicesArray = $this->paginateCustom(collect($records));

    $servicesArray->map(function ($item) {
      $item->package_empty = (count($item->packages) === 0) ? 1 : 0;
    });

    return  $servicesArray;
  }


  public function paginateCustom($data)
  {
    $page = request()->input('page', 1);
    $perPage = request()->input('limit', 10);
    $dataPaginated = new LengthAwarePaginator(
      $data->forPage($page, $perPage)->values(),
      $data->count(),
      $perPage,
      $page
    );

    $dataPaginated->setPath(request()->url());

    return $dataPaginated;
  }

  public function find($id)
  {
    $data = BusinessService::with(['service.packages:id,service_id,price,status', 'package', 'business'])->where('id', $id)->first();
    if (!$data) {
      throw new HttpException("Không tìm thấy dữ liệu !");
    }
    $arrayPricesBigger = array_filter(collect($data->service->packages)
      ->toArray(), function ($itemFilter) use ($data) {
      return $itemFilter['price'] > $data->package->price && $itemFilter['status'] == 1;
    });
    $data->is_upgrade = empty($arrayPricesBigger) ? 0 : 1;
    return $data;
  }

  public function create($data)
  {
    try {
      $result = $this->CreateOrUpdate($data);

      return $result;
    } catch (DatabaseException $e) {
      throw new DatabaseException($e->getMessage());
    }
  }

  public function createManyOfRow($data)
  {
    try {
      $result = $this->CreateManyRow($data);

      return $result;
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }

  public function update($data)
  {
    try {
      $this->id = $data[$this->primaryKey];
      $model = $this->CreateOrUpdate($data);
      if ($model) {
        return true;
      }
      return false;
    } catch (Exception $e) {
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
      if ($model) {
        return true;
      }
      return false;
    } catch (Exception $e) {
      $this->error = $e->getMessage();
      return false;
    }
  }

  public function delete($id)
  {

    $this->id = $id;
    $arrID = [$id];
    $model = $this->DeleteManyRow($arrID);

    if (!$model) {
      return false;
    }
    return true;
  }

  public function listPackage($request)
  {
    $id = $request->service_id;
    $packagePrice = null;
    $package = PackageService::where('id', $request->min_id)->first();
    if ($package) {
      $packagePrice =  $package->price;
    }
    $data = Service::with(['packages' => function ($query) use ($packagePrice, $request) {
      $query->where('status', 1);
      if ($packagePrice) {
        $query->where('price', '>', $packagePrice);
      }
      if (
        isset($request->package_id)
        && $request->package_id
      ) {
        $query->where('id', $request->package_id);
      }
      $query->orderBy('price', 'asc');
    }])
      ->where('id', $id)
      ->firstOrFail();

    $permissions = ModelRolePermission::where('role_id', $data->role_id)
      ->pluck(
        'permission_id'
      )->toArray();

    $modules = Module::whereIn('id', function ($query) use ($permissions) {
      $query
        ->select('module_id')
        ->from('ad_group_page_controls')
        ->whereIn('id', $permissions);
    })
      ->select(['id', 'title'])
      ->get()
      ->toArray();

    $data->modules = $modules;

    $modulesNoUsing = Module::whereJsonContains('type', 'business')
      ->whereNotIn(
        'id',
        array_column($modules, 'id')
      )
      ->select(['id', 'title'])
      ->get()
      ->toArray();
    $data->modules_no_using = $modulesNoUsing;
    return $data;
  }

  public function calculateOldPackage($request)
  {
    $response = [];
    $business_id = Auth::guard('api')->user()->business_id;
    // $business_id = 100;
    $current = BusinessService::with(['package.service'])
      ->where('business_id', $business_id)
      ->where('package_id', $request->current_id)
      ->first();
    if (!$current) {
      throw new HttpException("Không tìm thấy gói đang sử dụng !");
    }

    if ($current->package->status == 0) {
      throw new HttpException("Gói này đang không hoạt động !");
    }

    if ($request->mode == 'extend') {
      if ($current->package->extension_allowed == 0) {
        throw new HttpException("Gói này không được phép gia hạn !");
      }
    }

    $newPackage = null;
    if (isset($request->upgrade_id)) {
      $newPackage = PackageService::with(['service'])->where('id', $request->upgrade_id)->first();
      if (!$newPackage) {
        throw new HttpException("Không tìm thấy gói mới cần nâng cấp !");
      }

      if ($newPackage->status == 0) {
        throw new HttpException("Gói này đang không hoạt động !");
      }
    }

    if ($newPackage) {
      // lấy ngày kích hoạt , ngày hết hạn , ngày hôm nay
      $startDate = \Carbon\Carbon::createFromFormat(
        'Y-m-d',
        Carbon::parse($current->date_active)
          ->format('Y-m-d')
      );
      $endDate = \Carbon\Carbon::createFromFormat(
        'Y-m-d',
        Carbon::parse($current->date_expired)
          ->format('Y-m-d')
      );
      $currentDate = now();
      // lấy tháng  , thời gian của gói
      $months = $current->package->time;
      $price = $current->package->price;
      // tổng số ngày sử dụng , ngày đã sử dụng , tổng số tháng sử dụng
      $totalDayUsing =  (int) $startDate->diffInDays($endDate);
      $totalDayUsed =  (int) ($startDate->diffInDays($currentDate) + 1);
      $totalMonthUsing = (int) $startDate->diffInMonths($endDate);
      // tính tổng số tiền cho chọn gói, và giá tiền mỗi ngày
      $counterUsing =  $totalMonthUsing / $months;
      $totalAmountUsing = $counterUsing * $price;
      $amountPerDay = $totalAmountUsing / $totalDayUsing;
      // tính số tiền còn lại
      $totalAmountRemaining = round(($totalAmountUsing - ($amountPerDay * $totalDayUsed)));

      // gói mới
      $multiplier = ceil($totalAmountRemaining / $newPackage->price);
      $quantitySuggest = $multiplier;
      if (isset($request->quantity)) {
        $multiplier = $request->quantity;
      }

      if ($multiplier < $quantitySuggest) {
        throw new HttpException("Giá tiền gói nâng cấp phải lớn hơn bằng giá gói cũ đang dùng!");
      }
      $totalRecommend = $multiplier * $newPackage->price;
      $response = [
        'quantity_suggest' => $quantitySuggest,
        'quantity' => (int)$multiplier,
        'total_time' => $multiplier * $newPackage->time,
        'amount' => $newPackage->price,
        'old_package' => $totalAmountRemaining,
        'total_amount' => $totalRecommend - $totalAmountRemaining,
        'package' => collect($newPackage)->toArray(),
      ];
    } else {
      $quantity = $request->input('quantity', 1);
      $response = [
        'quantity' => (int)$quantity,
        'total_time' => $quantity * $current->package->time,
        'amount' =>  $current->package->price,
        'total_amount' => $quantity * $current->package->price,
        'package' => collect($current->package)->toArray(),
      ];
    }

    return $response;
  }
}
