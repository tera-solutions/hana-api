<?php

namespace App\Modules\Education\Lesson\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

/**
 * Status of a single activity within a lesson (or lesson-plan template),
 * shared by edu_lesson_activities and edu_lesson_plan_lesson_activities.
 */
enum LessonActivityStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chưa bắt đầu',
            self::InProgress => 'Đang tiến hành',
            self::Completed => 'Hoàn thành',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Neutral,
            self::InProgress => BadgeColor::Warning,
            self::Completed => BadgeColor::Success,
        };
    }
}
