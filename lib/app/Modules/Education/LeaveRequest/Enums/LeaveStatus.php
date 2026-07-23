<?php

namespace App\Modules\Education\LeaveRequest\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum LeaveStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Từ chối',
            self::Cancelled => 'Đã hủy',
            self::Completed => 'Hoàn thành',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Warning,
            self::Approved => BadgeColor::Success,
            self::Rejected => BadgeColor::Danger,
            self::Cancelled => BadgeColor::Neutral,
            self::Completed => BadgeColor::Info,
        };
    }
}
