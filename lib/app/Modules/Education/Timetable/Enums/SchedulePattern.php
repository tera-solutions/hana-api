<?php

namespace App\Modules\Education\Timetable\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum SchedulePattern: string implements HasLabel
{
    use ProvidesOptions;

    case FixedWeekly = 'fixed_weekly';
    case SpecificDates = 'specific_dates';

    public function label(): string
    {
        return match ($this) {
            self::FixedWeekly => 'Cố định theo tuần',
            self::SpecificDates => 'Theo ngày cụ thể',
        };
    }
}
