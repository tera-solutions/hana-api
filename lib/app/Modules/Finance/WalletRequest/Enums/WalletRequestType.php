<?php

namespace App\Modules\Finance\WalletRequest\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum WalletRequestType: string implements HasLabel
{
    use ProvidesOptions;

    case Deposit = 'deposit';
    case Withdraw = 'withdraw';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Nạp tiền',
            self::Withdraw => 'Rút tiền',
        };
    }
}
