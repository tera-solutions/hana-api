<?php

namespace App\Modules\Education\Student\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum StudentStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Active = 'active';
    case Suspended = 'suspended';
    case Graduated = 'graduated';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang học',
            self::Suspended => 'Bảo lưu',
            self::Graduated => 'Đã tốt nghiệp',
            self::Dropped => 'Đã nghỉ',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Active => BadgeColor::Info,
            self::Suspended => BadgeColor::Warning,
            self::Graduated => BadgeColor::Success,
            self::Dropped => BadgeColor::Neutral,
        };
    }
}
