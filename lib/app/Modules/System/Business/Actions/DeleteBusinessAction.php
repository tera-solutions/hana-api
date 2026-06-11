<?php

namespace App\Modules\System\Business\Actions;

use App\Modules\System\Business\Services\BusinessService;

class DeleteBusinessAction
{
    public function handle(...$params)
    {
        return app(BusinessService::class)->delete(...$params);
    }
}
