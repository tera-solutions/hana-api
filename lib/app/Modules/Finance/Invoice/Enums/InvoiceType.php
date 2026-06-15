<?php

namespace App\Modules\Finance\Invoice\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum InvoiceType: string implements HasLabel
{
    use ProvidesOptions;

    case Receivable = 'receivable';
    case Payable = 'payable';

    public function label(): string
    {
        return match ($this) {
            self::Receivable => 'Khoản phải thu',
            self::Payable => 'Khoản phải chi',
        };
    }
}
