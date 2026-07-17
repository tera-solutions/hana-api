<?php

namespace App\Modules\Finance\BankAccount\Actions;

use App\Modules\Finance\BankAccount\Services\BankAccountService;

class UpdateMyBankAccountAction
{
    public function handle(...$params)
    {
        return app(BankAccountService::class)->updateMine(...$params);
    }
}
