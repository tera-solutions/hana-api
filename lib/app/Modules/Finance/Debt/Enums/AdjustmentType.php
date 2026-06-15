<?php

namespace App\Modules\Finance\Debt\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Kinds of debt adjustment (debt.md §XI). Corrections/discounts reduce the
 * invoice; write-offs give up an uncollectible balance after approval (§XII).
 */
enum AdjustmentType: string implements HasLabel
{
    use ProvidesOptions;

    case Correction = 'correction';
    case Discount = 'discount';
    case WriteOff = 'write_off';

    public function label(): string
    {
        return match ($this) {
            self::Correction => 'Điều chỉnh hóa đơn',
            self::Discount => 'Giảm giá sau phát hành',
            self::WriteOff => 'Xóa nợ',
        };
    }
}
