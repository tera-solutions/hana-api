<?php

namespace App\Modules\System\Onboarding\Actions;

use App\Modules\System\Onboarding\Services\OnboardingService;

class RegisterBusinessAction
{
    public function handle(...$params)
    {
        return app(OnboardingService::class)->register(...$params);
    }
}
