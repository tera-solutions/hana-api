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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * @group Core - User
 *
 * Authenticated user's own profile.
 */
class UserController extends Controller
{

    /**
     * Get current profile
     *
     * Shows the profile of the logged-in user. Requires a bearer token and a `device-code` header.
     *
     * @authenticated
     *
     * @header Device-code {your-device-code}
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"id": 1, "username": "super", "full_name": "John Doe", "email": "super@example.com", "status": "active", "role": "admin", "role_name": "Administrator", "access_id": 123},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 401 {
     *   "success": false,
     *   "msg": "No permision!!",
     *   "data": null,
     *   "code": 401,
     *   "errors": []
     * }
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
            ->leftJoin("sys_roles", "sys_roles.id", "users.role_id")
            ->leftJoin('oauth_access_tokens', 'users.id', '=', 'oauth_access_tokens.user_id')
            ->where("users.status", "active")
            ->select([
                "users.*",
                'sys_roles.code as role',
                'sys_roles.title as role_name',
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

    /**
     * Update current profile
     *
     * Updates the logged-in user's own basic info. `avatar` is a URL already
     * uploaded via `POST /file/upload`.
     *
     * @authenticated
     *
     * @bodyParam full_name string The user's name. Example: Cô Ngọc
     * @bodyParam dob string Date of birth (Y-m-d). Example: 1990-03-15
     * @bodyParam gender string male, female or other. Example: female
     * @bodyParam phone string Phone number. Example: 0901234567
     * @bodyParam avatar string Avatar URL. Example: https://cdn.hana.edu.vn/a.png
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->respondWithError('No permision!!', [], 401);
        }

        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'dob' => ['sometimes', 'nullable', 'date'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $user->fill($data);
        $user->save();

        return $this->respondSuccess($user->fresh(), 'Cập nhật thông tin thành công.');
    }

    /**
     * Change current password
     *
     * Changes the logged-in user's own password (requires knowing the current one) —
     * distinct from the forgot-password/admin-reset flows.
     *
     * @authenticated
     *
     * @bodyParam current_password string required The user's current password.
     * @bodyParam new_password string required The new password (min 8 chars).
     */
    public function changePassword(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->respondWithError('No permision!!', [], 401);
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return $this->respondWithError('Mật khẩu hiện tại không đúng.', [], 422);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return $this->respondSuccess(null, 'Đổi mật khẩu thành công.');
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
