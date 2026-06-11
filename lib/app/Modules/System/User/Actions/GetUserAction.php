<?php

namespace App\Modules\System\User\Actions;

use App\Modules\System\User\Services\UserService;

class GetUserAction
{
    public function handle(...$params)
    {
        return app(UserService::class)->find(...$params);
    }
}
