<?php

namespace App\Modules\Education\Attendance\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum AttendanceStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Có mặt',
            self::Absent => 'Vắng mặt',
            self::Late => 'Đi muộn',
            self::Excused => 'Vắng có phép',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Present => BadgeColor::Success,
            self::Absent => BadgeColor::Neutral,
            self::Late => BadgeColor::Warning,
            self::Excused => BadgeColor::Info,
        };
    }
}
