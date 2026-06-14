<?php

namespace App\Modules\HR\Teacher\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum TeacherType: string implements HasLabel
{
    use ProvidesOptions;

    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Freelancer = 'freelancer';
    case Assistant = 'assistant';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Toàn thời gian',
            self::PartTime => 'Bán thời gian',
            self::Freelancer => 'Cộng tác viên',
            self::Assistant => 'Trợ giảng',
        };
    }
}
