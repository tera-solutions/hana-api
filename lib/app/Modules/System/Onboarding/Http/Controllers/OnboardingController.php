<?php

namespace App\Modules\System\Onboarding\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Onboarding\Actions\RegisterBusinessAction;
use App\Modules\System\Onboarding\Http\Requests\RegisterBusinessRequest;
use App\Modules\System\Onboarding\Http\Resources\OnboardingResource;

/**
 * @group System - Onboarding
 *
 * Public self-service registration for new centers (Teacher app).
 *
 * @unauthenticated
 */
class OnboardingController extends Controller
{
    /**
     * Register a center
     *
     * Creates an isolated tenant in one step: a Business, an owner Admin user,
     * a matching Teacher profile and a 14-day trial subscription. The owner can
     * log in immediately with the submitted email + password.
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Đăng ký thành công.",
     *   "data": {
     *     "business": {"id": 1, "name": "Hana English", "email": "owner@hana.edu.vn"},
     *     "user": {"id": 1, "code": "USR000001", "full_name": "Nguyen Van A", "email": "owner@hana.edu.vn"},
     *     "is_verify": 1
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function register(RegisterBusinessRequest $request, RegisterBusinessAction $action)
    {
        $result = $action->handle($request->validated());

        return $this->respondSuccess(new OnboardingResource($result), 'Đăng ký thành công.');
    }
}
