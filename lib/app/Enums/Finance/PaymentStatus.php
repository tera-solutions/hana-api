<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum PaymentStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Đang xử lý',
            self::Completed => 'Thành công',
            self::Failed => 'Thất bại',
            self::Refunded => 'Đã hoàn',
        };
    }
}
