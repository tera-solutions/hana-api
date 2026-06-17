<?php

namespace App\Modules\Education\LessonPlan\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum LessonPlanStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Draft = 'draft';
    case Reviewing = 'reviewing';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Đang soạn',
            self::Reviewing => 'Chờ duyệt',
            self::Published => 'Đang sử dụng',
            self::Archived => 'Ngừng sử dụng',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Reviewing => BadgeColor::Warning,
            self::Published => BadgeColor::Success,
            self::Archived => BadgeColor::Neutral,
        };
    }
}
