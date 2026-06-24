<?php

namespace App\Modules\Finance\Wallet\Actions;

use App\Modules\Finance\Wallet\Services\WalletService;

/**
 * Locks or unlocks a wallet by delegating to the matching service method.
 */
class LockWalletAction
{
    public function handle(string $transition, $id)
    {
        return app(WalletService::class)->{$transition}($id);
    }
}
