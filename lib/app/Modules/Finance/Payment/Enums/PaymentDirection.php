<?php

namespace App\Modules\Finance\Payment\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum PaymentDirection: string implements HasLabel
{
    use ProvidesOptions;

    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Thu tiền',
            self::Out => 'Chi tiền',
        };
    }
}
