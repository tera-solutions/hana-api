<?php

namespace App\Modules\Finance\WalletRequest\Actions;

use App\Modules\Finance\WalletRequest\Services\WalletRequestService;

class CreateWalletRequestAction
{
    public function handle(...$params)
    {
        return app(WalletRequestService::class)->create(...$params);
    }
}
