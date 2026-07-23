<?php

namespace App\Modules\Finance\BusinessBankAccount\Actions;

use App\Modules\Finance\BusinessBankAccount\Services\BusinessBankAccountService;

class ListBusinessBankAccountAction
{
    public function __construct(private BusinessBankAccountService $service) {}

    public function handle(array $params)
    {
        return $this->service->paginate($params);
    }
}
