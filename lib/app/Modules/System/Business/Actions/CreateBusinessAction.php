<?php

namespace App\Modules\System\Business\Actions;

use App\Modules\System\Business\Services\BusinessService;

class CreateBusinessAction
{
    public function handle(...$params)
    {
        return app(BusinessService::class)->create(...$params);
    }
}
