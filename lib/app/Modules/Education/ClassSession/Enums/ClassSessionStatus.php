<?php

namespace App\Modules\Education\ClassSession\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ClassSessionStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Sắp diễn ra',
            self::Ongoing => 'Đang diễn ra',
            self::Completed => 'Đã hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }
}
