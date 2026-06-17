<?php

namespace App\Modules\Education\Lesson\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum LessonStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Đã lên lịch',
            self::Confirmed => 'Đã xác nhận',
            self::InProgress => 'Đang diễn ra',
            self::Completed => 'Đã hoàn thành',
            self::Cancelled => 'Đã hủy',
            self::Locked => 'Đã khóa',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Scheduled => BadgeColor::Neutral,
            self::Confirmed => BadgeColor::Info,
            self::InProgress => BadgeColor::Warning,
            self::Completed => BadgeColor::Success,
            self::Cancelled => BadgeColor::Danger,
            self::Locked => BadgeColor::Neutral,
        };
    }
}
