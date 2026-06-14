<?php

namespace App\Modules\System\Branch\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum BranchStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang hoạt động',
            self::Inactive => 'Ngừng hoạt động',
            self::Suspended => 'Tạm ngưng',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Active => BadgeColor::Success,
            self::Inactive => BadgeColor::Neutral,
            self::Suspended => BadgeColor::Warning,
        };
    }
}
