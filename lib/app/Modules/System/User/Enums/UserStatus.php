<?php

namespace App\Modules\System\User\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum UserStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Active = 'active';
    case Inactive = 'inactive';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang hoạt động',
            self::Inactive => 'Ngừng hoạt động',
            self::Locked => 'Đã khóa',
        };
    }
}
