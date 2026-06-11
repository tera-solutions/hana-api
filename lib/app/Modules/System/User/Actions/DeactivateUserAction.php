<?php

namespace App\Modules\System\User\Actions;

use App\Modules\System\User\Services\UserService;

class DeactivateUserAction
{
    public function handle(...$params)
    {
        return app(UserService::class)->deactivate(...$params);
    }
}
