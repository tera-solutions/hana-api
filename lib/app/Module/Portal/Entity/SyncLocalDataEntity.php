<?php

namespace App\Module\Portal\Entity;

use App\Models\TableVersionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Package\Entity\AbstractEntity;
use Package\Entity\EntityInterface;
use App\Module\Portal\Repository\SyncLocalDataRepository;
use Package\Exception\AuthorizationException;

class SyncLocalDataEntity extends AbstractEntity implements EntityInterface
{

  /**
   * @var SyncLocalDataRepository
   */
  protected $repository;

  /**
   * @var
   */
  protected $errors;

  protected $permission;
  /**
   * RegisterEntity constructor.
   * @param SyncLocalDataRepository $repository
   */
  public function __construct(
    SyncLocalDataRepository $repository,
  ) {
    $this->repository = $repository;
  }


  public function pullChanges($request)
  {
    $token = $request->bearerToken();
    $ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    $browser = $request->header('User-Agent');

    $tableSync = [
      'generals',
      'business_locations',
    ];
    if (isset($request->tables)) {
      $tablesArray = array_map('trim', explode(',', $request->tables));
      $tablesArray = array_filter($tablesArray);
      if (count($tablesArray) > 0) {
        $tableSync = $tablesArray;
      }
    }

    $resData = [
      'status' => 'ok',
      'db_version' => env('DB_VERSION'),
      'timestamp' => now()->getTimestamp(),
      'last_pull_at' => now()->format("Y-m-d H:i:s"),
      "changes" => []
    ];

    $resData["changes"]['settings'] = [
      "agent" => !empty($browser) ? $browser : "default",
      "device_code" => $this->repository->generateRandomString(12),
      "device_type" => 0,
      "super" => true,
      "is_mobile" => $this->repository->isMobile(),
      "logo_url" => asset('/assets/setting/logo.png?v=' . env("VERSION")),
    ];
    if ($token) {
      $user = Auth::guard('api')->user();
      if ($user->business_id) {
        $allowedCRMTables = ['customers', 'products'];
        $crmTables = array_intersect($tableSync, $allowedCRMTables);

        if (count($crmTables) > 0) {
          $paramCRM = [
            'tables' => implode(', ', $crmTables),
            // 'updated_at' => $request->updated_at,
          ];
          $urlCRM = env("CRM_API") . '/api/sync/pull-change';

          $response = Http::withHeaders([
            'Accept' => 'application/json',
          ])->withToken($token)
            ->timeout(30)
            ->get($urlCRM, $paramCRM);

          if ($response->successful()) {
            $crmData = $response->json();
            if ($crmData["code"] === 200 && $crmData["success"] === true) {

              if (isset($crmData["data"]['changes']) && is_array($crmData["data"]['changes'])) {
                $resData["changes"] = array_merge($resData["changes"], $crmData["data"]['changes']);
              }
            }
          } else {
            Log::error("Sync Customers Fail: Status " . $response->status() . " - " . $response->body());
          }
        }

        $requestBusiness = (object) [
          "business_id" => $user->business_id,
        ];

        foreach ($tableSync as $table) {
          switch ($table) {
            case 'generals':
              $resData["changes"]['users'] = $user;
              $table_version = TableVersionLog::where('business_id', $user->business_id);
              if (!empty($request->table_name)) {
                $table_version->where("table_name", $request->table_name);
              }
              $resData["changes"]['table_version_logs'] = $table_version->get();

              $resData["changes"]['business'] = $this->repository->getBusiness($requestBusiness);
              break;
            case 'business_locations':
              $requestLocation = (object) [
                "page" => 0,
                "limit" => 100,
                "business_id" => $user->business_id,
                "updated_at" => $request->updated_at
              ];
              $resData["changes"]['business_locations'] = $this->repository->getBusinessLocation($requestLocation);
              $resData["changes"]['business'] = $this->repository->getBusiness($requestBusiness);
              break;
            case 'customers':
              break;
            default:
              break;
          }
        }
      }
    }

    return $resData;
  }

  public function pushChanges($request)
  {
    $business_locations = [];
    if (isset($request["changes"]["business_locations"])) {
      $business_locations = $request["changes"]["business_locations"];
    }

    $updated_at = now();
    if (isset($request["updated_at"])) {
      $updated_at = $request["updated_at"];
    }

    $this->repository->addQueue($request["changes"]);

    // return $this->repository->pushChanges($business_locations, $updated_at);
    return true;
  }
}
