<?php

namespace App\Modules\Finance\Wallet\Actions;

use App\Modules\Finance\Wallet\Services\WalletService;

class ListWalletTransactionAction
{
    public function handle(...$params)
    {
        return app(WalletService::class)->transactions(...$params);
    }
}
