<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum DebtStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Chưa trả',
            self::Partial => 'Trả một phần',
            self::Paid => 'Đã trả',
        };
    }
}
