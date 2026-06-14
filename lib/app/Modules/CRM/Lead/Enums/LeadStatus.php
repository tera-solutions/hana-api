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
    case Studying = 'studying';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xác nhận',
            self::Verified => 'Đã xác minh',
            self::Studying => 'Đang học',
            self::Inactive => 'Ngừng theo dõi',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Warning,
            self::Verified => BadgeColor::Success,
            self::Studying => BadgeColor::Info,
            self::Inactive => BadgeColor::Neutral,
        };
    }
}
