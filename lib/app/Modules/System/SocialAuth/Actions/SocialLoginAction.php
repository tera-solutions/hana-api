<?php

namespace App\Modules\System\SocialAuth\Actions;

use App\Modules\System\SocialAuth\Services\SocialAuthService;

class SocialLoginAction
{
    public function handle(...$params)
    {
        return app(SocialAuthService::class)->login(...$params);
    }
}
