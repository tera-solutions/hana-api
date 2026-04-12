<?php

namespace App\Module\Portal\Repository;

use App\Helpers\Task;
use App\Models\TableVersionLog;
use Illuminate\Support\Facades\DB;
use Package\Util\BasicEntity;
use Package\Repository\RepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Auth;
use Package\Exception\DatabaseException;
use Package\Exception\HttpException;
use App\Module\Portal\Model\BusinessLocation;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class BussinessLocationRepository extends BasicEntity implements RepositoryInterface
{
  public $table;

  public $primaryKey;

  public $fillable;

  public $hidden;

  public function __construct()
  {
    $locations = new BusinessLocation();
    $this->table = $locations->getTable();
    $this->fillable = $locations->getFillable();
    $this->hidden = $locations->getHidden();
    $this->primaryKey = $locations->getKeyName();
    array_push($this->fillable, $this->primaryKey);
  }

  public function all($request)
  {
    try {
      $business_id = request()->header('business-id');
      $locations = BusinessLocation::where('business_id', $business_id)
        ->where("is_delete", 0)
        ->select(['*']);

      $locations = $this->queryApplyFilter($locations, $request);

      $data = $locations->paginate($request->limit);


      $data->getCollection()->transform(function ($item, $key) use ($data) {
        $item->record_number = ($data->currentPage() - 1) * $data->perPage() + $key + 1;
        return $item;
      });


      $summaryQuery = BusinessLocation::where('business_id', $business_id)
        ->whereIn('is_active', [0, 1])
        ->where("is_delete", 0);
      $summaryQuery = $this->queryApplyFilter($summaryQuery, $request, false);
      $summaryQuery = $summaryQuery->select('is_active', DB::raw('count(*) as total_count'))->groupBy('is_active')
        ->get();

      $summary = $summaryQuery->toArray();

      $totalCount = $summaryQuery->sum('total_count');
      foreach ($summary as &$item) {
        $status = $item['is_active'];
        $item['status'] = $status;
        unset($item['is_active']);
        // $item = collect($item)->except([])->toArray();
      }

      array_push($summary, [
        'status' => 'all',
        'total_count' => $totalCount,
      ]);
      $response['data'] = $data;
      $response['summary'] = $summary;
      $response['timestamp'] = now()->timestamp;

      $tableLog = TableVersionLog::select("version")
        ->where(
          "table_name",
          "business_locations"
        )
        ->where("business_id", $business_id)
        ->first();
      $response['version'] = isset($tableLog["version"]) ? $tableLog["version"] : 0;

      return $response;
    } catch (DatabaseException $e) {
      $message = $e->getMessage();
      throw new DatabaseException($message);
    }
  }

  public function queryApplyFilter($object, $request, $flag = true)
  {
    if (isset($request->keyword) && $request->keyword) {
      $object->where(function ($query) use ($request) {
        $query->where("name", "LIKE", "%$request->keyword%");
        $query->orWhere("location_id", "LIKE", "%$request->keyword%");
      });
    }
    if (isset($request->view_all) && $request->view_all) {
      $object->whereIn('is_active', [1, 0, null]);
    } else {
      $object->where('is_active', 1);
    }
    if ($flag) {
      if (isset($request->status)) {
        $object->where('is_active', $request->status);
      }
    }

    if (isset($request->updated_at) && $request->updated_at) {
      $object->where('updated_at', '>', $request->updated_at);
    }

    return $object;
  }

  public function find($id)
  {
    DB::beginTransaction();
    try {
      $business_id = request()->header('business-id');
      $user_id = Auth::guard('api')->user()->id;

      $location = BusinessLocation::where('business_id', $business_id)->findOrFail($id);

      return $location;
    } catch (DatabaseException $e) {
      DB::rollBack();
      $message = $e->getMessage();
      throw new DatabaseException($message);
    }
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
    return $this->findAllTrash($pagin);
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
    DB::beginTransaction();

    try {
      $business_id = request()->header('business-id');
      $user_id = Auth::guard('api')->user()->id;
      $location = BusinessLocation::where('business_id', $business_id)
        ->find($id);

      if (!$location) {
        throw new HttpException("Chi nhánh không tồn tại !");
      }

      $location->delete();
      DB::commit();
      return $location;
    } catch (DatabaseException $e) {
      DB::rollBack();
      $message = $e->getMessage();
      throw new DatabaseException($message);
    }
  }


  public function syncDataLocal($changes)
  {
    DB::beginTransaction();
    try {
      $result = false;
      $business_id = request()->header('business-id');
      $user_id = Auth::guard('api')->user()->id;

      $tableLog = TableVersionLog::where(
          "table_name",
          "business_locations"
        )
        ->where("business_id", $business_id)
        ->first();
      $tableVersion = isset($tableLog["version"]) ? $tableLog["version"] : 0;

      if (count($changes["created"]) > 0 || count($changes["updated"]) > 0) {
        $dataChanges = collect(array_merge($changes['created'], $changes['updated']))
          ->map(function ($item) use ($business_id, $user_id) {
            $ref_no = null;
            if (empty($item["location_id"])) {
              $ref_count = Task::setAndGetReferenceCount("location", $business_id);
              $ref_no = Task::generateReferenceNumber("location", $ref_count);
            }

            return [
              "id" => isset($item["server_id"]) ? $item["server_id"] : null,
              "client_id" => isset($item["id"]) ? $item["id"] : null,
              "business_id" => $business_id,
              "location_id" => isset($item["location_id"]) ? $item["location_id"] : $ref_no,
              "name" => isset($item["name"]) ? $item["name"] : null,
              "mobile" => isset($item["mobile"]) ? $item["mobile"] : null,
              "landmark" => isset($item["landmark"]) ? $item["landmark"] : null,
              "ward" => isset($item["ward"]) ? $item["ward"] : null,
              "state" => isset($item["state"]) ? $item["state"] : null,
              "city" => isset($item["city"]) ? $item["city"] : "Việt Nam",
              "country" => isset($item["country"]) ? $item["country"] : "",
              "is_default" => $item["is_default"] ? 1 : 0,
              "is_new_address" => $item["is_new_address"] ? 1 : 0,
              "created_by" => $user_id,
              "updated_at" => now()
            ];
          });

        $hasNewDefault = $dataChanges->contains('is_default', 1);

        if ($hasNewDefault) {
          BusinessLocation::where('business_id', $business_id)
          ->update(["is_default" => 0, 'updated_at' => DB::raw('updated_at')]);
        }

        $dataToSync = $dataChanges->toArray();

        $result = BusinessLocation::upsert(
          $dataToSync,
          ['id'],
          [
            'name',
            'mobile',
            'landmark',
            'ward',
            'city',
            'is_default',
            'is_new_address',
            'location_id',
            'client_id',
            'updated_at'
          ]
        );
        Log::channel('sync')->info('Đã cập nhật dữ liệu sync ' . count($dataToSync) . ' records data từ máy khách user: ' . $user_id . " | ", $dataToSync);
        if (!$result) {
          DB::rollBack();
          return false;
        }

        if ($tableLog) {
          $tableLog->increment('version');
          $tableLog->update([
            'updated_at' => now()
          ]);
          $tableLog->save();
        } else {
          TableVersionLog::firstOrCreate(
            ['table_name' => 'business_locations'],
            ['version' => 0, 'business_id' => $business_id]
          );
        }

        DB::commit();

        return BusinessLocation::where('business_id', $business_id)
          ->where("is_delete", 0)
          ->where("updated_at", ">=", $changes["updated_at"])
          ->get();
      }
      if (count($changes["deleted"]) > 0) {
        $result = BusinessLocation::where('business_id', $business_id)
          ->whereIn('client_id', $changes["deleted"])
          ->update(['is_delete' => 1, 'updated_at' => now()]);
        if (!$result) {
          DB::rollBack();
          return false;
        }

        if ($tableLog) {
          $tableLog->increment('version');
          $tableLog->update([
            'updated_at' => now()
          ]);
          $tableLog->save();
        }
        DB::commit();

        Log::channel('sync')->info('Đã xóa dữ liệu sync ' . count($changes["deleted"]) . ' records data từ máy khách user: ' . $user_id . " | ", $changes["deleted"]);
        return BusinessLocation::where('business_id', $business_id)
          ->where("is_delete", 0)
          ->where("updated_at", ">=", $changes["updated_at"])
          ->get();

      }
      return $result;
    } catch (DatabaseException $e) {
      DB::rollBack();
      throw new DatabaseException($e->getMessage());
    }
  }

  public function createData($request)
  {
    DB::beginTransaction();

    try {
      $input = $request->only(['location_id', 'name', 'landmark', 'state', 'city', 'ward', 'mobile', 'email', 'website', 'is_active', 'is_default', 'is_new_address', 'invoice_scheme_id', 'invoice_layout_id']);
      $business_id = request()->header('business-id');
      $user_id = Auth::guard('api')->user()->id;

      $input['business_id'] = $business_id;
      $input['created_by'] = $user_id;
      $input['is_default'] = $input['is_default'] ? 1 : 0;
      $input['is_new_address'] = $input['is_new_address'] ? 1 : 0;

      if ($request->location_id) {
        $check = BusinessLocation::where('business_id', $business_id)->where('location_id', trim($request->location_id))->first();
        if ($check) {
          throw new HttpException("Mã đã tồn tại !");
        }
      } else {
        $ref_count = Task::setAndGetReferenceCount("location", $business_id);
        $ref_no = Task::generateReferenceNumber("location", $ref_count);

        $input['location_id'] = $ref_no;
      }

      if (isset($input['is_default']) && $input['is_default'] == true) {
        BusinessLocation::where('business_id', $business_id)->update(["is_default" => 0]);
      }

      $location = BusinessLocation::create($input);

      if (!$location) {
        DB::rollBack();
        $message = "Lỗi trong quá trình tạo cửa hàng";
        throw new HttpException($message);
      }

      DB::commit();
      return $location;
    } catch (DatabaseException $e) {
      DB::rollBack();
      $message = $e->getMessage();
      throw new DatabaseException($message);
    }
  }

  public function updateData($request, $id)
  {
    DB::beginTransaction();
    try {
      $input = $request->only(['location_id', 'name', 'landmark', 'state', 'city', 'ward', 'mobile', 'email', 'website', 'is_active', 'is_default', 'is_new_address', 'invoice_scheme_id', 'invoice_layout_id']);
      $business_id = request()->header('business-id');
      $user_id = Auth::guard('api')->user()->id;
      $check = BusinessLocation::where('business_id', $business_id)->where('id', '!=', $id)->where('location_id', trim($request->location_id))->first();
      if ($check) {
        throw new HttpException("Mã đã tồn tại !");
      }
      $location = BusinessLocation::where('business_id', $business_id)->find($id);

      if (!$location) {
        throw new HttpException("Chi nhánh không tồn tại !");
      }

      if (isset($input['name'])) {
        $location->name = $input['name'];
      }

      if (isset($input['landmark'])) {
        $location->landmark = $input['landmark'];
      }

      if (isset($input['state'])) {
        $location->state = $input['state'];
      }

      if (isset($input['city'])) {
        $location->city = $input['city'];
      }

      if (isset($input['ward'])) {
        $location->ward = $input['ward'];
      }

      if (isset($input['mobile'])) {
        $location->mobile = $input['mobile'];
      }

      if (isset($input['email'])) {
        $location->email = $input['email'];
      }

      if (isset($input['website'])) {
        $location->website = $input['website'];
      }

      if (isset($input['is_active'])) {
        $location->is_active = $input['is_active'];
      }

      if (isset($input['location_id'])) {
        $location->location_id = $input['location_id'];
      }

      if ($input['is_default']) {
        BusinessLocation::where('business_id', $business_id)->update(["is_default" => 0]);
      }

      $location->is_default = $input['is_default'] ? 1 : 0;
      $location->is_new_address = $input['is_new_address'] ? 1 : 0;

      $location->save();

      DB::commit();

      return $location;
    } catch (DatabaseException $e) {
      DB::rollBack();
      $message = $e->getMessage();
      throw new DatabaseException($message);
    }
  }

  public function importData($request)
  {
    DB::beginTransaction();

    try {
      $business_id = request()->header('business-id');
      $user_id = Auth::guard('api')->user()->id;

      //Set maximum php execution time
      ini_set('max_execution_time', 0);
      ini_set('memory_limit', -1);

      if ($request->hasFile('file')) {
        $file = $request->file('file');

        $parsed_array = Excel::toArray([], $file);

        //Remove header row
        $imported_data = array_splice($parsed_array[0], 1);

        $formated_data = [];
        $prices_data = [];

        $is_valid = true;
        $error_msg = '';

        $total_rows = count($imported_data);

        foreach ($imported_data as $key => $value) {
          //Check if any column is missing
          if (count($value) < 2) {
            $is_valid = false;
            $error_msg = "Thiếu cột trong quá trình tải lên dữ liệu. vui lòng tuần thủ dữ template";
            break;
          }

          $row_no = $key + 1;
          $location_array = [];
          $location_array['business_id'] = $business_id;
          $location_array['created_by'] = $user_id;


          //Add SKU
          $actual_name = trim($value[0]);
          if (!empty($actual_name)) {
            $location_array['name'] = $actual_name;
            //Check if product with same SKU already exist
            $is_exist = BusinessLocation::where('name', $location_array['name'])
              ->where('business_id', $business_id)
              ->exists();
            if ($is_exist) {
              $is_valid = false;
              $error_msg = "Tên cửa hàng : $actual_name đã tồn tại ở dòng thứ. $row_no";
              break;
            }
          } else {
            $is_valid = false;
            $error_msg = "Thiếu tên cửa hàng";
            break;
          }

          //Add product name
          $description = trim($value[1]);
          if (!empty($description)) {
            $location_array['description'] = $description;
          }

          $formated_data[] = $location_array;
        }

        if (!$is_valid) {
          throw new \Exception($error_msg);
        }

        if (!empty($formated_data)) {
          foreach ($formated_data as $index => $location_data) {
            //Create new product
            BusinessLocation::create($location_data);
          }
        }
      }

      DB::commit();
      return true;
    } catch (DatabaseException $e) {
      DB::rollBack();
      $message = $e->getMessage();
      throw new DatabaseException($message);
    }
  }
}
