<?php

namespace App\Module\Portal\Jobs;

use App\Mail\VerifyOTP;
use App\Module\Portal\Mails\VerifyOTPTransaction;
use App\Module\Portal\Repository\SyncLocalDataRepository;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SyncLocalDataQueue implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  protected $changes;
  protected $business_id;
  protected $user_id;
  protected $token;

  public function __construct($changes, $token, $business_id, $user_id)
  {
    $this->changes = $changes;
    $this->business_id = $business_id;
    $this->user_id = $user_id;
    $this->token = $token;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    try {
      Log::info("run queue");
      Log::info($this->business_id);
      Log::info($this->user_id);
      if (empty($this->changes))
        throw new \Exception("Not found changes");

      $crmTables = [
        "customers"
      ];

      $portalTables = [
        "business_locations"
      ];
      $dataQueueCRM = [];

      foreach ($this->changes as $tableName => $actions) {
        // Log::info($tableName);
        // Log::info($actions);
        if (in_array($tableName, $crmTables)) {
          $dataQueueCRM[$tableName] = $actions;
        } else if (in_array($tableName, $portalTables)) {
          switch ($tableName) {
            case 'business_locations':
              $result = app(SyncLocalDataRepository::class)->syncBusinessLocation($actions, $this->user_id, $this->business_id);
              if (!$result) {
                throw new \Exception("Fail run job business_locations: " . $result);
              }
              Log::info("============== Đã đồng bộ thành công  business_locations ==============");
              break;
            default:
              break;
          }
        }
      }

      if (count($dataQueueCRM) > 0) {
        $urlCRM = env("CRM_API") . '/api/sync/push-change';

        $response = Http::withHeaders([
          'Accept' => 'application/json',
          'business-id' => $this->business_id,
          'user-id' => $this->user_id,
        ])->withToken($this->token)
          ->timeout(30)
          ->post(
            $urlCRM,
            [
              "changes" => $dataQueueCRM
            ]
          );

        if ($response->successful()) {
          $crmData = $response->json();
          Log::info($crmData);
          if ($crmData["code"] !== 200 || $crmData["success"] !== true) {
            throw new \Exception("Sync Data CRM Fail: " . $crmData["message"]);
          }
          Log::info("============== Đã đồng bộ thành công  CRM ==============");
          return;
        } else {
          throw new \Exception("Sync Data CRM Fail: " . $response->status());
        }
      }
      // throw new \Exception("No job for queue: ");
    } catch (\Exception $th) {
      throw $th;
    }
  }
}
