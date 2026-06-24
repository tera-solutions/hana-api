<?php

namespace App\Modules\Education\Evaluation\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum EvaluationStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Nháp',
            self::Submitted => 'Đã gửi',
            self::Approved => 'Đã duyệt',
            self::Rejected => 'Từ chối',
            self::Locked => 'Khóa',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Submitted => BadgeColor::Info,
            self::Approved => BadgeColor::Success,
            self::Rejected => BadgeColor::Danger,
            self::Locked => BadgeColor::Warning,
        };
    }
}
