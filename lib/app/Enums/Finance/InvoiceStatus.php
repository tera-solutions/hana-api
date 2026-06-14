<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum InvoiceStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chưa thanh toán',
            self::Partial => 'Thanh toán một phần',
            self::Paid => 'Đã thanh toán',
            self::Cancelled => 'Đã hủy',
        };
    }
}
