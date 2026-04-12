<?php

namespace App\Module\Portal\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Module\Portal\Entity\UserEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    protected $user;
    public function __construct(UserEntity $user)
    {
        $this->user = $user;
    }

    /**
     * Shows profile of logged in user
     *
     * @return \Illuminate\Http\Response
     */
    public function getProfile()
    {
        $result = $this->user->getProfile();
        return $this->respondSuccess($result);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $result =  $this->user->changePassword($request);
        return $this->respondSuccess($result);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $input = $request->all();
        $result = $this->user->updateProfile($input);
        return $this->respondSuccess($result, 'Cập Nhật Thông Tin Thành Công');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAvatar(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            $token = Auth::guard('api')->user()->token();
            $token->revoke();
            $user_id = Auth::guard('api')->user()->id;
            $session = Session::where("user_id", $user_id)->first();
            if ($session) {
                $session->delete();
            }
            return $this->respondWithError("Tài khoản của bạn đã hết hạn!", [], 401);
        }

        $input = $request->all();
        $files = $request->file_upload;

        if (isset($files["url"])) {
            $filePath = $files["url"];
            $urlImage = !empty($filePath) ? str_replace(url("/"), "", $filePath) : "";
            $input['avatar'] = $urlImage;
        }

        $result = $this->user->updateAvatar($input);
        if (!$result) {
            return $this->respondWithError("Mật Khẩu Cũ Không Đúng!", [], 401);
        }
        return $this->respondSuccess($result, 'Cập nhật Ảnh Đại Diện Thành Công');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeSetting(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            $token = Auth::guard('api')->user()->token();
            $token->revoke();
            $user_id = Auth::guard('api')->user()->id;
            $session = Session::where("user_id", $user_id)->first();
            if ($session) {
                $session->delete();
            }
            return $this->respondWithError("Tài khoản của bạn đã hết hạn!", [], 401);
        }
        $input = $request->all();
        $result = $this->user->changeSetting($input);
        if (!$result) {
            return $this->respondWithError("Thay Đổi Cài Đặt Thất Bại!", [], 401);
        }
        return $this->respondSuccess($result, 'Thay Đổi Cài Đặt Thành Công');
    }

    public function changeLanguage(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            $token = Auth::guard('api')->user()->token();
            $token->revoke();
            $user_id = Auth::guard('api')->user()->id;
            $session = Session::where("user_id", $user_id)->first();
            if ($session) {
                $session->delete();
            }
            return $this->respondWithError("Tài khoản của bạn đã hết hạn!", [], 401);
        }
        $input = $request->all();
        $result = $this->user->changeLanguage($input);
        if (!$result) {
            return $this->respondWithError("Thay Đổi Ngôn Ngữ Thất Bại!", [], 401);
        }
        return $this->respondSuccess($result, 'Thay Đổi Ngôn Ngữ Thành Công');
    }
}
