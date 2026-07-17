<?php

namespace App\Modules\Finance\WalletRequest\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

/**
 * pending --approve--> approved --complete--> completed (ledger entry written here).
 * pending --reject--> rejected. pending --cancel--> cancelled (by the requester).
 */
enum WalletRequestStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ duyệt',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Từ chối',
            self::Completed => 'Hoàn tất',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Warning,
            self::Approved => BadgeColor::Info,
            self::Rejected => BadgeColor::Neutral,
            self::Completed => BadgeColor::Success,
            self::Cancelled => BadgeColor::Neutral,
        };
    }
}
