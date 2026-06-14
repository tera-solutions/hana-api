<?php

namespace App\Modules\Education\Attendance\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum AttendanceStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Present = 'present';
    case Absent = 'absent';
    case Late = 'late';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Có mặt',
            self::Absent => 'Vắng mặt',
            self::Late => 'Đi muộn',
            self::Excused => 'Vắng có phép',
        };
    }
}
