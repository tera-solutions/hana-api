<?php

namespace App\Modules\System\SocialAuth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\SocialAuth\Actions\SocialLoginAction;
use App\Modules\System\SocialAuth\Http\Requests\SocialLoginRequest;

/**
 * @group Core - Authentication
 *
 * "Login with Google/Microsoft" — verifies the id_token the FE got from the
 * provider's own JS SDK (Google Identity Services / MSAL.js) and returns the
 * same token envelope as `POST /auth/login`, so the FE reuses its normal
 * post-login handling.
 */
class SocialAuthController extends Controller
{
    /**
     * Social login
     *
     * Matches an existing account by the provider's verified email, or
     * self-registers a new center + owner account (same as self-signup) when
     * no account exists yet with that email.
     *
     * @bodyParam provider string required google | microsoft. Example: google
     * @bodyParam id_token string required The provider's id_token (JWT). Example: eyJhbGciOiJSUzI1NiIs...
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Đăng nhập thành công !",
     *   "data": {
     *     "verify_auth": 0,
     *     "user": {"id": 1, "username": "teacher@gmail.com", "full_name": "John Doe", "email": "teacher@gmail.com"},
     *     "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
     *     "refresh_token": null,
     *     "expires_in": 15552000,
     *     "access_id": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Invalid token" {
     *   "success": false,
     *   "msg": "Google token không hợp lệ hoặc đã hết hạn.",
     *   "data": null,
     *   "code": 500,
     *   "errors": []
     * }
     */
    public function login(SocialLoginRequest $request, SocialLoginAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($request->validated('provider'), $request->validated('id_token')),
            'Đăng nhập thành công !',
        );
    }
}
