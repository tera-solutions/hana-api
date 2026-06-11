<?php

namespace App\Modules\System\Business\Actions;

use App\Modules\System\Business\Services\BusinessService;

class UpdateBusinessAction
{
    public function handle(...$params)
    {
        return app(BusinessService::class)->update(...$params);
    }
}
