<?php

namespace App\Modules\Finance\BusinessBankAccount\Actions;

use App\Modules\Finance\BusinessBankAccount\Services\BusinessBankAccountService;

class CreateBusinessBankAccountAction
{
    public function __construct(private BusinessBankAccountService $service) {}

    public function handle(array $data)
    {
        return $this->service->create($data);
    }
}
