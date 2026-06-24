<?php

namespace App\Modules\Education\Evaluation\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum EvaluationClassification: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Excellent = 'excellent';
    case Good = 'good';
    case Average = 'average';
    case Weak = 'weak';
    case Warning = 'warning';

    public function label(): string
    {
        return match ($this) {
            self::Excellent => 'Xuất sắc',
            self::Good => 'Tốt',
            self::Average => 'Trung bình',
            self::Weak => 'Cần cải thiện',
            self::Warning => 'Cảnh báo',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Excellent => BadgeColor::Success,
            self::Good => BadgeColor::Info,
            self::Average => BadgeColor::Warning,
            self::Weak => BadgeColor::Neutral,
            self::Warning => BadgeColor::Danger,
        };
    }
}
