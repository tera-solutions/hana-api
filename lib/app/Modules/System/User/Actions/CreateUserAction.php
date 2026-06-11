<?php

namespace App\Modules\System\User\Actions;

use App\Modules\System\User\Services\UserService;

class CreateUserAction
{
    public function handle(...$params)
    {
        return app(UserService::class)->create(...$params);
    }
}
