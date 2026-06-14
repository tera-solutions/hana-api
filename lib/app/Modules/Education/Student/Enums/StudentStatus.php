<?php

namespace App\Modules\Education\Student\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum StudentStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Active = 'active';
    case Suspended = 'suspended';
    case Graduated = 'graduated';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang học',
            self::Suspended => 'Bảo lưu',
            self::Graduated => 'Đã tốt nghiệp',
            self::Dropped => 'Đã nghỉ',
        };
    }
}
