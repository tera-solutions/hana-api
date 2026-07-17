<?php

namespace App\Modules\Finance\WalletRequest\Actions;

use App\Modules\Finance\WalletRequest\Services\WalletRequestService;

class GetWalletRequestAction
{
    public function handle(...$params)
    {
        return app(WalletRequestService::class)->find(...$params);
    }
}
