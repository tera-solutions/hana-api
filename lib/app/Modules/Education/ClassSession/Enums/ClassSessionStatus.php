<?php

namespace App\Modules\Education\ClassSession\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum ClassSessionStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Sắp diễn ra',
            self::Ongoing => 'Đang diễn ra',
            self::Completed => 'Đã hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Upcoming => BadgeColor::Warning,
            self::Ongoing => BadgeColor::Info,
            self::Completed => BadgeColor::Success,
            self::Cancelled => BadgeColor::Neutral,
        };
    }
}
