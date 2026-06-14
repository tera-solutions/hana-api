<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum PaymentMethod: string implements HasLabel
{
    use ProvidesOptions;

    case Cash = 'cash';
    case Transfer = 'transfer';
    case Card = 'card';
    case Wallet = 'wallet';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tiền mặt',
            self::Transfer => 'Chuyển khoản',
            self::Card => 'Thẻ',
            self::Wallet => 'Ví điện tử',
            self::Other => 'Khác',
        };
    }
}
