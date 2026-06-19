<?php

namespace App\Modules\System\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\User\Actions\ActivateUserAction;
use App\Modules\System\User\Actions\CreateUserAction;
use App\Modules\System\User\Actions\DeactivateUserAction;
use App\Modules\System\User\Actions\DeleteUserAction;
use App\Modules\System\User\Actions\GetUserAction;
use App\Modules\System\User\Actions\ListUserAction;
use App\Modules\System\User\Actions\ResetPasswordUserAction;
use App\Modules\System\User\Actions\UnlockUserAction;
use App\Modules\System\User\Actions\UpdateUserAction;
use App\Modules\System\User\Http\Requests\CreateUserRequest;
use App\Modules\System\User\Http\Requests\UpdateUserRequest;
use App\Modules\System\User\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * @group System - User
 *
 * Manage system user accounts.
 *
 * @authenticated
 */
class UserController extends Controller
{
    /**
     * List users
     *
     * @queryParam search string Search by code, full name, email or phone. Example: super
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam branch_id integer Filter by branch id. Example: 1
     * @queryParam role_id integer Filter by role id. Example: 2
     * @queryParam status string Filter by status. Example: active
     * @queryParam created_from date Created on or after (Y-m-d). Example: 2026-01-01
     * @queryParam created_to date Created on or before (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sort column: code, full_name, email, created_at, status. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {"id": 1, "user_id": "U001", "username": "super", "full_name": "John Doe", "email": "super@example.com", "status": "active", "is_active": true, "is_admin": false, "business_id": 1, "branch_id": 1, "role_id": 2}
     *     ],
     *     "pagination": {"total": 1, "per_page": 15, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListUserAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), UserResource::class);
    }

    /**
     * User detail
     *
     * @urlParam id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"id": 1, "user_id": "U001", "username": "super", "full_name": "John Doe", "email": "super@example.com", "status": "active", "is_active": true, "is_admin": false, "role_id": 2},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetUserAction $action)
    {
        return $this->respondSuccess(new UserResource($action->handle($id)));
    }

    /**
     * Create user
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo người dùng thành công.",
     *   "data": {"id": 1, "user_id": "U001", "username": "super", "full_name": "John Doe", "email": "super@example.com", "status": "active", "is_active": true},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateUserRequest $request, CreateUserAction $action)
    {
        $user = $action->handle($request->validated());

        return $this->respondSuccess(new UserResource($user), 'Tạo người dùng thành công.');
    }

    /**
     * Update user
     *
     * @urlParam id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật người dùng thành công.",
     *   "data": {"id": 1, "user_id": "U001", "username": "super", "full_name": "John A. Doe", "email": "super@example.com", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateUserRequest $request, $id, UpdateUserAction $action)
    {
        $user = $action->handle($id, $request->validated());

        return $this->respondSuccess(new UserResource($user), 'Cập nhật người dùng thành công.');
    }

    /**
     * Delete (deactivate) user
     *
     * @urlParam id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Người dùng đã được vô hiệu hóa.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function delete($id, DeleteUserAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Người dùng đã được vô hiệu hóa.');
    }

    /**
     * Activate user
     *
     * @urlParam id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Kích hoạt tài khoản thành công.",
     *   "data": {"id": 1, "username": "super", "status": "active", "is_active": true},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function activate($id, ActivateUserAction $action)
    {
        return $this->respondSuccess(new UserResource($action->handle($id)), 'Kích hoạt tài khoản thành công.');
    }

    /**
     * Deactivate user
     *
     * @urlParam id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Vô hiệu hóa tài khoản thành công.",
     *   "data": {"id": 1, "username": "super", "status": "inactive", "is_active": false},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function deactivate($id, DeactivateUserAction $action)
    {
        return $this->respondSuccess(new UserResource($action->handle($id)), 'Vô hiệu hóa tài khoản thành công.');
    }

    /**
     * Unlock user
     *
     * @urlParam id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Mở khóa tài khoản thành công.",
     *   "data": {"id": 1, "username": "super", "status": "active", "is_active": true},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function unlock($id, UnlockUserAction $action)
    {
        return $this->respondSuccess(new UserResource($action->handle($id)), 'Mở khóa tài khoản thành công.');
    }

    /**
     * Reset user password
     *
     * Generates a new password for the user and returns it once.
     *
     * @urlParam id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Đặt lại mật khẩu thành công.",
     *   "data": {
     *     "user": {"id": 1, "username": "super", "full_name": "John Doe"},
     *     "password": "Xy7$kPq2"
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function resetPassword($id, ResetPasswordUserAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'user' => new UserResource($result['user']),
            'password' => $result['password'],
        ], 'Đặt lại mật khẩu thành công.');
    }
}
