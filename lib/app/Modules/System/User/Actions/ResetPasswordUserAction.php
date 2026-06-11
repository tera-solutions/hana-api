<?php

namespace App\Modules\System\User\Actions;

use App\Modules\System\User\Services\UserService;

class ResetPasswordUserAction
{
    public function handle(...$params)
    {
        return app(UserService::class)->resetPassword(...$params);
    }
}
