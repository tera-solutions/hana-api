<?php

namespace App\Modules\Finance\Account\Actions;

use App\Modules\Finance\Account\Services\AccountService;

class CreateAccountAction
{
    public function handle(...$params)
    {
        return app(AccountService::class)->create(...$params);
    }
}
