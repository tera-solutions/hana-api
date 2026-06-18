<?php

namespace App\Modules\Education\Material\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum MaterialStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Draft = 'draft';
    case Active = 'active';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Active => 'Đang dùng',
        };
    }
}
