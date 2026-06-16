<?php

namespace App\Modules\Finance\Invoice\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum InvoiceStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case PendingPayment = 'pending_payment';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Pending => 'Chưa thanh toán',
            self::Approved => 'Đã duyệt',
            self::PendingPayment => 'Chờ thanh toán',
            self::Partial => 'Thanh toán một phần',
            self::Paid => 'Đã thanh toán',
            self::Cancelled => 'Đã hủy',
            self::Refunded => 'Đã hoàn tiền',
            self::Closed => 'Đã đóng',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Pending => BadgeColor::Warning,
            self::Approved => BadgeColor::Info,
            self::PendingPayment => BadgeColor::Warning,
            self::Partial => BadgeColor::Info,
            self::Paid => BadgeColor::Success,
            self::Cancelled => BadgeColor::Neutral,
            self::Refunded => BadgeColor::Warning,
            self::Closed => BadgeColor::Neutral,
        };
    }
}
