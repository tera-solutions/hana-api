<?php

namespace App\Modules\Finance\Promotion\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ReferralStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Pending = 'pending';
    case Rewarded = 'rewarded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ thưởng',
            self::Rewarded => 'Đã thưởng',
            self::Cancelled => 'Đã hủy',
        };
    }
}
