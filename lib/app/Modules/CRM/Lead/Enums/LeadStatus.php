<?php

namespace App\Modules\CRM\Lead\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum LeadStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Pending = 'pending';
    case Verified = 'verified';
    case Consulting = 'consulting';
    case Studying = 'studying';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Mới',
            self::Verified => 'Đang chăm sóc',
            self::Consulting => 'Đã hẹn tư vấn',
            self::Studying => 'Đã ghi danh',
            self::Inactive => 'Không tiềm năng',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Info,
            self::Verified => BadgeColor::Warning,
            self::Consulting => BadgeColor::Danger,
            self::Studying => BadgeColor::Success,
            self::Inactive => BadgeColor::Neutral,
        };
    }
}
