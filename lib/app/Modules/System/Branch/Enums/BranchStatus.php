<?php

namespace App\Modules\System\Branch\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum BranchStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang hoạt động',
            self::Inactive => 'Ngừng hoạt động',
            self::Suspended => 'Tạm ngưng',
        };
    }
}
