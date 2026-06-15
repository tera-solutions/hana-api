<?php

namespace App\Modules\Finance\Account\Actions;

use App\Modules\Finance\Account\Services\AccountService;

class RestoreAccountAction
{
    public function handle(...$params)
    {
        return app(AccountService::class)->restore(...$params);
    }
}
