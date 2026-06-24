<?php

namespace App\Modules\Finance\Wallet\Actions;

use App\Modules\Finance\Wallet\Services\WalletService;

class GetWalletAction
{
    public function handle(...$params)
    {
        return app(WalletService::class)->find(...$params);
    }
}
