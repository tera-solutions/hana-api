<?php

namespace App\Modules\Finance\Promotion\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum VoucherStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Hiệu lực',
            self::Used => 'Đã dùng',
            self::Expired => 'Hết hạn',
            self::Locked => 'Đã khóa',
        };
    }
}
