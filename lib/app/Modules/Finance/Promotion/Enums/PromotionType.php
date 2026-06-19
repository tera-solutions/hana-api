<?php

namespace App\Modules\Finance\Promotion\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum PromotionType: string implements HasLabel
{
    use ProvidesOptions;

    case Discount = 'discount';
    case GiftLesson = 'gift_lesson';
    case WalletCredit = 'wallet_credit';
    case Voucher = 'voucher';
    case Referral = 'referral';
    case Combo = 'combo';

    public function label(): string
    {
        return match ($this) {
            self::Discount => 'Giảm giá',
            self::GiftLesson => 'Tặng buổi học',
            self::WalletCredit => 'Tặng credit',
            self::Voucher => 'Voucher',
            self::Referral => 'Giới thiệu',
            self::Combo => 'Combo',
        };
    }
}
