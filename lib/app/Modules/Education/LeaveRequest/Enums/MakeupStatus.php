<?php

namespace App\Modules\Education\LeaveRequest\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum MakeupStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Waiting = 'waiting';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Chờ xếp lịch',
            self::Scheduled => 'Đã xếp lịch',
            self::Completed => 'Hoàn thành',
            self::Expired => 'Hết hạn',
        };
    }
}
