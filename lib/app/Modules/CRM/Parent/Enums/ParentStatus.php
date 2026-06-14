<?php

namespace App\Modules\CRM\Parent\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ParentStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang hoạt động',
            self::Suspended => 'Tạm ngưng',
            self::Inactive => 'Ngừng hoạt động',
        };
    }
}
