<?php

namespace App\Module\Portal\Repository;

use App\Helpers\Task;
use App\Models\Job;
use App\Models\TableVersionLog;
use App\Module\Portal\Jobs\SyncLocalDataQueue;
use App\Module\Portal\Model\Business;
use Carbon\Carbon;
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

class SyncLocalDataRepository extends BasicEntity
{
  public function __construct()
  {
    //
  }

  public function getBusiness($request)
  {
    try {
      $business = Business::where('id', $request->business_id)
        ->where("is_delete", 0)
        ->select([
          'id',
          'name',
          'email',
          'owner_phone',
          'address',
          'employee_size',
          'currency_id',
          'currency_symbol_placement',
          'date_format',
          'time_format',
          'accounting_method',
          'time_zone',
          'theme_color',
          'logo',
          'default_sales_discount',
          'sell_price_tax',
          'stop_selling_before',
          'expiry_type',
          'on_product_expiry',
          'transaction_edit_days',
          'stock_expiry_alert_days',
          'enable_brand',
          'enable_category',
          'enable_sub_category',
          'enable_price_tax',
          'enable_purchase_status',
          'enable_lot_number',
          'default_unit',
        ]);

      $locations = $this->queryApplyFilter($business, $request);

      $data = $locations->first();

      return $data;
    } catch (DatabaseException $e) {
      $message = $e->getMessage();
      throw new DatabaseException($message);
    }
  }

  public function getBusinessLocation($request)
  {
    try {
      $query = BusinessLocation::where('business_id', $request->business_id)
        ->select(['*']);

      $query = $this->queryApplyFilter($query, $request);
      $created = [];
      $updated = [];
      $deleted = [];

      if (isset($request->updated_at) && $request->updated_at) {
        $lastPulledDate = Carbon::parse($request->updated_at)->subMinutes(1);
        $baseQuery = clone $query;

        $updated = (clone $baseQuery)
          ->where("is_delete", 0)
          ->where('updated_at', '>=', $lastPulledDate)
          ->offset($request->page)
          ->limit($request->limit)
          ->orderBy("updated_at", "desc")
          ->get();
        $deleted = (clone $baseQuery)->where("is_delete", 1)
          ->where('deleted_at', '>=', $lastPulledDate)
          ->offset($request->page)
          ->limit($request->limit)
          ->orderBy("deleted_at", "desc")
          ->pluck('client_id')
          ->toArray();

      } else {
        $updated = $query->where("is_delete", 0)->get();
      }

      return [
        "created" => $created,
        "updated" => $updated,
        "deleted" => $deleted,
      ];
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

    return $object;
  }

  public function syncBusinessLocation($changes = [], $user_id, $business_id)
  {
    DB::beginTransaction();
    try {
      $result = false;

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
              'updated_at' => now()
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
          ['client_id', 'id'],
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
      }
      if (count($changes["deleted"]) > 0) {
        $result = BusinessLocation::where('business_id', $business_id)
          ->whereIn('client_id', $changes["deleted"])
          ->update(['is_delete' => 1, 'deleted_at' => now()]);
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
      }
      return true;
    } catch (DatabaseException $e) {
      DB::rollBack();
      throw new DatabaseException($e->getMessage());
    }
  }

  public function addQueue($changes)
  {

    DB::beginTransaction();
    try {
      $result = false;
      $token = request()->bearerToken();
      $business_id = request()->header('business-id');
      $user_id = Auth::guard('api')->user()->id;

      $crmTables = [
        "customers"
      ];

      $portalTables = [
        "business_locations"
      ];

      $dataQueuePortal = [];
      $dataQueueCRM = [];

      foreach ($changes as $tableName => $actions) {
        if (in_array($tableName, $crmTables)) {
          $dataQueueCRM[$tableName] = $actions;
        } else if (in_array($tableName, $portalTables)) {
          $dataQueuePortal[$tableName] = $actions;
        }
      }

      if (!empty($dataQueueCRM)) {
        Log::info("ADD NEW QUEUE CRM: ", $dataQueueCRM);
        SyncLocalDataQueue::dispatch($dataQueueCRM, $token, $business_id, $user_id);
      }
      if (!empty($dataQueuePortal)) {
        Log::info("ADD NEW QUEUE PORTAL: ", $dataQueueCRM);
        SyncLocalDataQueue::dispatch($dataQueuePortal, $token, $business_id, $user_id);
      }
      DB::commit();

      return true;
    } catch (DatabaseException $e) {
      DB::rollBack();
      throw new DatabaseException($e->getMessage());
    }
  }

  public function isMobile()
  {
    $useragent = $_SERVER['HTTP_USER_AGENT'];

    if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
      return true;
    } else {
      return false;
    }
  }

  public function generateRandomString($length = 10)
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }
}
