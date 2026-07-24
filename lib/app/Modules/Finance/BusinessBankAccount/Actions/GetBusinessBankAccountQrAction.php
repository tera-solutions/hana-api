<?php

namespace App\Modules\Finance\BusinessBankAccount\Actions;

use App\Modules\Finance\BusinessBankAccount\Services\BusinessBankAccountService;

class GetBusinessBankAccountQrAction
{
    public function __construct(private BusinessBankAccountService $service) {}

    public function handle($id): array
    {
        return $this->service->qr($id);
    }
}
