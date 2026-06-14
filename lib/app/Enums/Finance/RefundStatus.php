<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum RefundStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Pending = 'pending';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Đang xử lý',
            self::Completed => 'Đã hoàn',
            self::Rejected => 'Từ chối',
        };
    }
}
