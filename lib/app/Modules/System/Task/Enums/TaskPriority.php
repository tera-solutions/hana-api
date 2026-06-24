<?php

namespace App\Modules\System\Task\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum TaskPriority: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Thấp',
            self::Medium => 'Trung bình',
            self::High => 'Cao',
            self::Urgent => 'Khẩn cấp',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Low => BadgeColor::Neutral,
            self::Medium => BadgeColor::Info,
            self::High => BadgeColor::Warning,
            self::Urgent => BadgeColor::Danger,
        };
    }
}
