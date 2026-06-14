<?php

namespace App\Modules\Education\ClassRoom\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum ClassStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Draft = 'draft';
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Suspended = 'suspended';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Upcoming => 'Sắp khai giảng',
            self::Active => 'Đang hoạt động',
            self::Suspended => 'Tạm ngưng',
            self::Completed => 'Đã kết thúc',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Upcoming => BadgeColor::Warning,
            self::Active => BadgeColor::Info,
            self::Suspended => BadgeColor::Neutral,
            self::Completed => BadgeColor::Success,
        };
    }
}
