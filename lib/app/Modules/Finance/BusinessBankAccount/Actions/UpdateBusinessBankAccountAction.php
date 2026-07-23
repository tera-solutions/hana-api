<?php

namespace App\Modules\Finance\BusinessBankAccount\Actions;

use App\Modules\Finance\BusinessBankAccount\Services\BusinessBankAccountService;

class UpdateBusinessBankAccountAction
{
    public function __construct(private BusinessBankAccountService $service) {}

    public function handle($id, array $data)
    {
        return $this->service->update($id, $data);
    }
}
