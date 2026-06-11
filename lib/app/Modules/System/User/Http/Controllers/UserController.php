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
 */
class UserController extends Controller
{
    public function list(Request $request, ListUserAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), UserResource::class);
    }

    public function detail($id, GetUserAction $action)
    {
        return $this->respondSuccess(new UserResource($action->handle($id)));
    }

    public function create(CreateUserRequest $request, CreateUserAction $action)
    {
        $user = $action->handle($request->validated());

        return $this->respondSuccess(new UserResource($user), 'Tạo người dùng thành công.');
    }

    public function update(UpdateUserRequest $request, $id, UpdateUserAction $action)
    {
        $user = $action->handle($id, $request->validated());

        return $this->respondSuccess(new UserResource($user), 'Cập nhật người dùng thành công.');
    }

    public function delete($id, DeleteUserAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Người dùng đã được vô hiệu hóa.');
    }

    public function activate($id, ActivateUserAction $action)
    {
        return $this->respondSuccess(new UserResource($action->handle($id)), 'Kích hoạt tài khoản thành công.');
    }

    public function deactivate($id, DeactivateUserAction $action)
    {
        return $this->respondSuccess(new UserResource($action->handle($id)), 'Vô hiệu hóa tài khoản thành công.');
    }

    public function unlock($id, UnlockUserAction $action)
    {
        return $this->respondSuccess(new UserResource($action->handle($id)), 'Mở khóa tài khoản thành công.');
    }

    public function resetPassword($id, ResetPasswordUserAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'user' => new UserResource($result['user']),
            'password' => $result['password'],
        ], 'Đặt lại mật khẩu thành công.');
    }
}
