<?php

namespace App\Modules\Finance\BankAccount\Actions;

use App\Modules\Finance\BankAccount\Services\BankAccountService;

class GetMyBankAccountAction
{
    public function handle(...$params)
    {
        return app(BankAccountService::class)->mine(...$params);
    }
}
