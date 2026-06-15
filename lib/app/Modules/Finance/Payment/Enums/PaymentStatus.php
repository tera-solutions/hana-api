<?php

namespace App\Modules\Finance\Payment\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

/**
 * Payment lifecycle (payment.md §XIV). Only `confirmed` affects fund balances (BR-03).
 */
enum PaymentStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Draft = 'draft';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Pending => 'Chờ xác nhận',
            self::Confirmed => 'Đã xác nhận',
            self::Cancelled => 'Đã hủy',
            self::Reversed => 'Đã đảo',
            self::Refunded => 'Đã hoàn tiền',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Pending => BadgeColor::Warning,
            self::Confirmed => BadgeColor::Success,
            self::Cancelled => BadgeColor::Neutral,
            self::Reversed => BadgeColor::Info,
            self::Refunded => BadgeColor::Warning,
        };
    }
}
