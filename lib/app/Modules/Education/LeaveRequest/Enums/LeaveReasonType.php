<?php

namespace App\Modules\Education\LeaveRequest\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum LeaveReasonType: string implements HasLabel
{
    use ProvidesOptions;

    case Sick = 'sick';
    case Family = 'family';
    case SchoolActivity = 'school_activity';
    case Vacation = 'vacation';
    case Personal = 'personal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Sick => 'Bệnh',
            self::Family => 'Việc gia đình',
            self::SchoolActivity => 'Hoạt động ở trường',
            self::Vacation => 'Du lịch',
            self::Personal => 'Cá nhân',
            self::Other => 'Khác',
        };
    }
}
