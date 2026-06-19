<?php

namespace App\Modules\Finance\Promotion\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum DiscountType: string implements HasLabel
{
    use ProvidesOptions;

    case Percent = 'percent';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Theo phần trăm',
            self::Fixed => 'Số tiền cố định',
        };
    }
}
