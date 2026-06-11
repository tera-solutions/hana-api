<?php

namespace App\Modules\System\User\Actions;

use App\Modules\System\User\Services\UserService;

class UpdateUserAction
{
    public function handle(...$params)
    {
        return app(UserService::class)->update(...$params);
    }
}
