<?php

namespace App\Modules\Finance\BusinessBankAccount\Actions;

use App\Modules\Finance\BusinessBankAccount\Services\BusinessBankAccountService;

class SuspendBusinessBankAccountAction
{
    public function __construct(private BusinessBankAccountService $service) {}

    public function handle($id)
    {
        return $this->service->suspend($id);
    }
}
