<?php

namespace App\Modules\Education\Exam\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum RegistrationStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Registered = 'registered';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Absent = 'absent';
    case Graded = 'graded';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Đã đăng ký',
            self::InProgress => 'Đang thi',
            self::Submitted => 'Đã nộp',
            self::Absent => 'Vắng thi',
            self::Graded => 'Đã chấm',
            self::Published => 'Đã công bố',
        };
    }
}
