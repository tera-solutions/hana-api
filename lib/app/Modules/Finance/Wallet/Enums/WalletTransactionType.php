<?php

namespace App\Modules\Finance\Wallet\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum WalletTransactionType: string implements HasLabel
{
    use ProvidesOptions;

    case Deposit = 'deposit';
    case Payment = 'payment';
    case Refund = 'refund';
    case Bonus = 'bonus';
    case Salary = 'salary';
    case Adjustment = 'adjustment';
    case Expire = 'expire';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Nạp tiền',
            self::Payment => 'Thanh toán',
            self::Refund => 'Hoàn tiền',
            self::Bonus => 'Thưởng',
            self::Salary => 'Trả lương',
            self::Adjustment => 'Điều chỉnh',
            self::Expire => 'Hết hạn',
        };
    }
}
