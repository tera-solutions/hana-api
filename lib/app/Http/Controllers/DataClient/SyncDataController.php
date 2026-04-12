<?php
namespace App\Http\Controllers\DataClient;

use App\Models\TableVersionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SyncDataController extends Controller
{

    public function pullServer(Request $request)
    {
        try {
            $token = $request->bearerToken();

            $resData = [
                'status' => 'ok',
                'version' => env('DB_VERSION'),
                'timestamp' => now()->toIso8601String(),
            ];

            if ($token) {
                $user = Auth::guard('api')->user();
                if ($user->business_id) {
                    $table_version = TableVersionLog::where('business_id', $user->business_id);
                    if(!empty($request->table_name)) {
                        $table_version->where("table_name", $request->table_name);
                    }
                    $resData['table_version'] = $table_version->get();
                }
            }

            return $this->respondSuccess($resData);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage(), [], 500);
        }

    }

    public function pushServer(Request $request)
    {
        try {
            $token = $request->bearerToken();

            $resData = [
                'status' => 'ok',
                'version' => env('DB_VERSION'),
                'timestamp' => now()->toIso8601String(),
            ];

            if ($token) {
                $user = Auth::guard('api')->user();
                if ($user->business_id) {
                    $table_version = TableVersionLog::where('business_id', $user->business_id)->get();
                    $resData['table_version'] = $table_version;
                }
            }

            return $this->respondSuccess($resData);
        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage(), [], 500);
        }

    }
}