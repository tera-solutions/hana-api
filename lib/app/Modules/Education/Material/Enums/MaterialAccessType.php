<?php

namespace App\Modules\Education\Material\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum MaterialAccessType: string implements HasLabel
{
    use ProvidesOptions;

    case Teacher = 'teacher';
    case Student = 'student';
    case Parent = 'parent';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Teacher => 'Giáo viên',
            self::Student => 'Học viên',
            self::Parent => 'Phụ huynh',
            self::Internal => 'Nội bộ',
        };
    }
}
