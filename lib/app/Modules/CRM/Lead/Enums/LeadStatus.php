<?php

namespace App\Modules\CRM\Lead\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum LeadStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Pending = 'pending';
    case Verified = 'verified';
    case Studying = 'studying';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xác nhận',
            self::Verified => 'Đã xác minh',
            self::Studying => 'Đang học',
            self::Inactive => 'Ngừng theo dõi',
        };
    }
}
