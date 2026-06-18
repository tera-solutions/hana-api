<?php

namespace App\Modules\Education\Assignment\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum SubmissionStatus: string implements HasLabel
{
    use ProvidesOptions;

    case Assigned = 'assigned';
    case Submitted = 'submitted';
    case LateSubmitted = 'late_submitted';
    case Graded = 'graded';
    case Reviewed = 'reviewed';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Đã giao',
            self::Submitted => 'Đã nộp',
            self::LateSubmitted => 'Nộp trễ',
            self::Graded => 'Đã chấm',
            self::Reviewed => 'Đã công bố',
        };
    }
}
