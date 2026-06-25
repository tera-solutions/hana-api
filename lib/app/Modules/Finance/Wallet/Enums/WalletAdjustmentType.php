<?php

namespace App\Modules\Finance\Wallet\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum WalletAdjustmentType: string implements HasLabel
{
    use ProvidesOptions;

    case Increase = 'increase';
    case Decrease = 'decrease';

    public function label(): string
    {
        return match ($this) {
            self::Increase => 'Điều chỉnh tăng',
            self::Decrease => 'Điều chỉnh giảm',
        };
    }
}
