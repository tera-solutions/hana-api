<?php

namespace App\Modules\Finance\Promotion\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum PromotionStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Paused = 'paused';
    case Expired = 'expired';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Pending => 'Chờ duyệt',
            self::Active => 'Đang chạy',
            self::Paused => 'Tạm ngưng',
            self::Expired => 'Hết hạn',
            self::Closed => 'Kết thúc',
        };
    }
}
