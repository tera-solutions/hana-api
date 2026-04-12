<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use App\Models\Session;
use App\Models\Application;
use App\Models\PageView;
use App\Models\StockCRM;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    /**
     * Shows profile of logged in user
     *
     * @return \Illuminate\Http\Response
     */
    public function getProfile(Request $request)
    {
        if (!Auth::guard('api')->user()) {
            return $this->respondWithError("No permision!!", [], 401);
        }

        $user_id = Auth::guard('api')->user()->id;

        $device_code = $request->header("device-code");

        if (!$device_code) {
            return $this->respondWithError("Thiết bị bị từ chối!", [], 500);
        }

        $user = User::where('users.id', $user_id)
            ->leftJoin("roles", "roles.id",  "users.role_id")
            ->leftJoin('oauth_access_tokens','users.id','=','oauth_access_tokens.user_id')
            ->where("users.status", "1")
            ->select([
                "users.*",
                'users.reps_login',
                'roles.code as role',
                'roles.title as role_name',
                'oauth_access_tokens.id as access_id'
            ]);

        $data = $user->first();
        if (!$data) {
            $token = Auth::guard('api')->user()->token();

            $token->revoke();

            $user_id = Auth::guard('api')->user()->id;

            $session = Session::where("user_id", $user_id)->first();
            if ($session) {
                $session->delete();
            }

            return $this->respondWithError("Tài khoản của bạn đã hết hạn!", [], 401);
        }
        return $this->respondSuccess($data);
    }

    public function getPermission(Request $request)
    {
        if (!Auth::guard('api')->user()) {
            return $this->respondWithError("No permision!!", [], 401);
        }

        $user_id = Auth::guard('api')->user()->id;
        $role_id = Auth::guard('api')->user()->role_id;

        $device_code = $request->header("device-code");

        if (!$device_code) {
            return $this->respondWithError("Thiết bị bị từ chối!", [], 500);
        }

        $res = RolePermission::where('role_id', $role_id)
            ->pluck("code");

        return $this->respondSuccess($res);
    }

    public function getModules(Request $request)
    {
        if (!Auth::guard('api')->user()) {
            return $this->respondWithError("No permision!!", [], 401);
        }

        $user_id = Auth::guard('api')->user()->id;
        $role_id = Auth::guard('api')->user()->role_id;

        $device_code = $request->header("device-code");

        if (!$device_code) {
            return $this->respondWithError("Thiết bị bị từ chối!", [], 500);
        }

        $app = Application::all();

        return $this->respondSuccess($app);
    }


    public function getEpic(Request $request)
    {
        if (!Auth::guard('api')->user()) {
            return $this->respondWithError("No permision!!", [], 401);
        }

        $user_id = Auth::guard('api')->user()->id;
        $role_id = Auth::guard('api')->user()->role_id;

        $device_code = $request->header("device-code");

        if (!$device_code) {
            return $this->respondWithError("Thiết bị bị từ chối!", [], 500);
        }

        if (empty($request->module)) {
            return $this->respondWithError("Bạn không có quyền sử dụng module này", [], 500);
        }


        $res = PageView::where('group', $request->module)
            ->pluck("code");

        return $this->respondSuccess($res);
    }
}
