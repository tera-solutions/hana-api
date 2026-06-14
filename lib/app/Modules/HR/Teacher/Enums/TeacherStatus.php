<?php

namespace App\Modules\HR\Teacher\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum TeacherStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Active = 'active';
    case Suspended = 'suspended';
    case Resigned = 'resigned';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang làm việc',
            self::Suspended => 'Tạm ngưng',
            self::Resigned => 'Đã nghỉ việc',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Active => BadgeColor::Success,
            self::Suspended => BadgeColor::Warning,
            self::Resigned => BadgeColor::Neutral,
        };
    }
}
