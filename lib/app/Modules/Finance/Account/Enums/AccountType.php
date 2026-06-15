<?php

namespace App\Modules\Finance\Account\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Fund (quỹ) types backing payments (payment.md §VI).
 */
enum AccountType: string implements HasLabel
{
    use ProvidesOptions;

    case Cash = 'cash';
    case Bank = 'bank';
    case Ewallet = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tiền mặt',
            self::Bank => 'Ngân hàng',
            self::Ewallet => 'Ví điện tử',
        };
    }
}
