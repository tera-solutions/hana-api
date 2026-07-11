<?php

namespace App\Modules\Finance\Wallet\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum WalletOwnerType: string implements HasLabel
{
    use ProvidesOptions;

    case Parent = 'parent';
    case Customer = 'customer';
    case Teacher = 'teacher';

    public function label(): string
    {
        return match ($this) {
            self::Parent => 'Phụ huynh',
            self::Customer => 'Khách hàng',
            self::Teacher => 'Giáo viên',
        };
    }
}
