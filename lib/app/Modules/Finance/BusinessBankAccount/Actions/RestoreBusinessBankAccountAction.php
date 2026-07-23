<?php

namespace App\Modules\Finance\BusinessBankAccount\Actions;

use App\Modules\Finance\BusinessBankAccount\Services\BusinessBankAccountService;

class RestoreBusinessBankAccountAction
{
    public function __construct(private BusinessBankAccountService $service) {}

    public function handle($id)
    {
        return $this->service->restore($id);
    }
}
