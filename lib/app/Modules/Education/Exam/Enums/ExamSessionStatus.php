<?php

namespace App\Modules\Education\Exam\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ExamSessionStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Đã lên lịch',
            self::InProgress => 'Đang diễn ra',
            self::Closed => 'Đã đóng',
        };
    }
}
