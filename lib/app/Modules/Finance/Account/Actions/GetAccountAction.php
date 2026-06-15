<?php

namespace App\Modules\Finance\Account\Actions;

use App\Modules\Finance\Account\Services\AccountService;

class GetAccountAction
{
    public function handle(...$params)
    {
        return app(AccountService::class)->find(...$params);
    }
}
