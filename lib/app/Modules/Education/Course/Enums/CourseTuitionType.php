<?php

namespace App\Modules\Education\Course\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Display/classification label for how a course's tuition is framed —
 * purely informational, doesn't change how Enrollment/Invoice compute
 * amounts (still `price_per_lesson` × lessons either way).
 */
enum CourseTuitionType: string implements HasLabel
{
    use ProvidesOptions;

    case PerLesson = 'per_lesson';
    case PerCourse = 'per_course';
    case PerMonth = 'per_month';

    public function label(): string
    {
        return match ($this) {
            self::PerLesson => 'Theo buổi',
            self::PerCourse => 'Trọn khóa',
            self::PerMonth => 'Theo tháng',
        };
    }
}
