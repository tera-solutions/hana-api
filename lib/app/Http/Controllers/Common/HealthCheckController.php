<?php
namespace App\Http\Controllers\Common;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @group Core - Health
 *
 * Liveness / connectivity probes. These endpoints are public (no token required).
 */
class HealthCheckController extends Controller
{
    /**
     * Health check
     *
     * Basic liveness probe.
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"status": "ok", "app_version": 1.0, "timestamp": "2026-06-12T00:00:00+00:00"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function index(Request $request)
    {
        $data = [
            'status' => 'ok',
            'app_version' => (float)env('APP_VERSION'),
            'timestamp' => now()->toIso8601String(),
        ];

        return $this->respondSuccess($data);
    }

    /**
     * Connectivity check
     *
     * Reports database / cache / storage / auth status. If a bearer token is supplied,
     * the authenticated user is included.
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "status": "ok",
     *     "agent": "Mozilla/5.0",
     *     "ip": "127.0.0.1",
     *     "timestamp": "2026-06-12T00:00:00+00:00",
     *     "services": {"database": "ok", "cache": "ok", "storage": "ok", "authenticated": "active"}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     * @response 401 {
     *   "success": false,
     *   "msg": "USER EXPIRED!!",
     *   "data": null,
     *   "code": 401,
     *   "errors": {"status": "expired"}
     * }
     */
    public function checkConnect(Request $request)
    {
        try {
            $ip = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
            $browser = $request->header('User-Agent');
            $browser = str_replace('', '', $browser);

            $token = $request->bearerToken();

            $status = [
                'status' => 'ok',
                "agent" => $browser,
                'ip' => $ip,
                'timestamp' => now()->toIso8601String(),
                'services' => [
                    'database' => $this->checkDatabase(),
                    'cache' => $this->checkCache(),
                    'storage' => $this->checkStorage(),
                    'authenticated' => $token ? 'active' : 'expired'
                ]
            ];

            if ($token) {
                $user = Auth::guard('api')->user();
                if ($user) {
                    $status['user'] = $user;
                }
            }

            if (collect($status['services'])->contains('expired')) {
                $status['status'] = 'expired';
                return $this->respondWithError("USER EXPIRED!!", $status, 401);
            }

            if (collect($status['services'])->contains('fail')) {
                $status['status'] = 'error';
                return $this->respondWithServerError("SERVER ERROR!!", $status, 503);
            }

            return $this->respondSuccess($status);
        } catch (\Exception $e) {
            return $this->respondWithServerError($e->getMessage(), [], 500);
        }

    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return 'ok';
        } catch (\Exception $e) {
            return 'fail';
        }
    }

    private function checkCache()
    {
        try {
            Cache::put('health_check', true, 5);
            return Cache::get('health_check') ? 'ok' : 'fail';
        } catch (\Exception $e) {
            return 'fail';
        }
    }

    private function checkStorage()
    {
        try {
            return Storage::disk('local')->put('health.txt', 'ok') ? 'ok' : 'fail';
        } catch (\Exception $e) {
            return 'fail';
        }
    }
}