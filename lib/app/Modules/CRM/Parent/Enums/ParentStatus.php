<?php

namespace App\Modules\CRM\Parent\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum ParentStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang hoạt động',
            self::Suspended => 'Tạm ngưng',
            self::Inactive => 'Ngừng hoạt động',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Active => BadgeColor::Success,
            self::Suspended => BadgeColor::Warning,
            self::Inactive => BadgeColor::Neutral,
        };
    }
}
