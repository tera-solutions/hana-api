<?php

namespace App\Modules\Finance\Debt\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

/**
 * Computed state of an outstanding debt (debt.md §V). Derived from the invoice's
 * balance, due date and any approved write-off — never stored directly (BR-06).
 */
enum DebtStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Current = 'current';
    case Overdue = 'overdue';
    case WrittenOff = 'written_off';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Trong hạn',
            self::Overdue => 'Quá hạn',
            self::WrittenOff => 'Đã xóa nợ',
            self::Closed => 'Đã tất toán',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Current => BadgeColor::Info,
            self::Overdue => BadgeColor::Danger,
            self::WrittenOff => BadgeColor::Neutral,
            self::Closed => BadgeColor::Success,
        };
    }
}
