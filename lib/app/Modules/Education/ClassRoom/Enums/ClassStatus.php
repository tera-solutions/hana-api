<?php

namespace App\Modules\Education\ClassRoom\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ClassStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Draft = 'draft';
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Suspended = 'suspended';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Upcoming => 'Sắp khai giảng',
            self::Active => 'Đang hoạt động',
            self::Suspended => 'Tạm ngưng',
            self::Completed => 'Đã kết thúc',
        };
    }
}
