<?php

namespace App\Modules\System\User\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum UserStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Active = 'active';
    case Inactive = 'inactive';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang hoạt động',
            self::Inactive => 'Ngừng hoạt động',
            self::Locked => 'Đã khóa',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Active => BadgeColor::Success,
            self::Inactive => BadgeColor::Neutral,
            self::Locked => BadgeColor::Neutral,
        };
    }
}
