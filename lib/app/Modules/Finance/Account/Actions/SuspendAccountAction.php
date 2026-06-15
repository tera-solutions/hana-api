<?php

namespace App\Modules\Finance\Account\Actions;

use App\Modules\Finance\Account\Services\AccountService;

class SuspendAccountAction
{
    public function handle(...$params)
    {
        return app(AccountService::class)->suspend(...$params);
    }
}
