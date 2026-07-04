<?php

namespace App\Modules\System\Task\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum TaskStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Draft = 'draft';
    case Open = 'open';
    case InProgress = 'in_progress';
    case PendingReview = 'pending_review';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Open => 'Mới tạo',
            self::InProgress => 'Đang thực hiện',
            self::PendingReview => 'Chờ duyệt',
            self::Completed => 'Hoàn thành',
            self::Rejected => 'Từ chối',
            self::Cancelled => 'Hủy',
            self::Overdue => 'Quá hạn',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Open => BadgeColor::Info,
            self::InProgress => BadgeColor::Info,
            self::PendingReview => BadgeColor::Warning,
            self::Completed => BadgeColor::Success,
            self::Rejected => BadgeColor::Danger,
            self::Cancelled => BadgeColor::Neutral,
            self::Overdue => BadgeColor::Danger,
        };
    }
}
