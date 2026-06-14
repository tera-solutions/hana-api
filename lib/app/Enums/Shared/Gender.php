<?php

namespace App\Enums\Shared;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum Gender: string implements HasLabel
{
    use ProvidesOptions;

    case Male = 'male';
    case Female = 'female';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Nam',
            self::Female => 'Nữ',
            self::Other => 'Khác',
        };
    }
}
