<?php

namespace App\Modules\Finance\Wallet\Actions;

use App\Modules\Finance\Wallet\Services\WalletService;

/**
 * Drives a balance operation (deposit / payment / refund / adjust / recordFromInvoice /
 * recordFromPayment) by delegating to the matching service method.
 */
class WalletTransactionAction
{
    public function handle(string $operation, array $data)
    {
        return app(WalletService::class)->{$operation}($data);
    }
}
