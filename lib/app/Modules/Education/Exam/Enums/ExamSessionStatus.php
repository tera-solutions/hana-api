<?php

namespace App\Modules\Education\Exam\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum ExamSessionStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Đã lên lịch',
            self::InProgress => 'Đang diễn ra',
            self::Closed => 'Đã đóng',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Scheduled => BadgeColor::Warning,
            self::InProgress => BadgeColor::Info,
            self::Closed => BadgeColor::Success,
        };
    }
}
