<?php

namespace App\Modules\HR\Teacher\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum TeacherStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Active = 'active';
    case Suspended = 'suspended';
    case Resigned = 'resigned';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang làm việc',
            self::Suspended => 'Tạm ngưng',
            self::Resigned => 'Đã nghỉ việc',
        };
    }
}
