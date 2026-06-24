<?php

namespace App\Modules\Finance\Wallet\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum WalletStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Active = 'active';
    case Locked = 'locked';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Hoạt động',
            self::Locked => 'Đã khóa',
            self::Closed => 'Đã đóng',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Active => BadgeColor::Success,
            self::Locked => BadgeColor::Warning,
            self::Closed => BadgeColor::Neutral,
        };
    }
}
