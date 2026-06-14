<?php

namespace App\Modules\Education\Enrollment\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum EnrollmentStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Pending = 'pending';
    case Studying = 'studying';
    case Suspended = 'suspended';
    case Transferred = 'transferred';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xác nhận',
            self::Studying => 'Đang học',
            self::Suspended => 'Bảo lưu',
            self::Transferred => 'Chuyển lớp',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Hủy đăng ký',
            self::Refunded => 'Hoàn phí',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Pending => BadgeColor::Warning,
            self::Studying => BadgeColor::Info,
            self::Suspended => BadgeColor::Warning,
            self::Transferred => BadgeColor::Info,
            self::Completed => BadgeColor::Success,
            self::Cancelled => BadgeColor::Neutral,
            self::Refunded => BadgeColor::Warning,
        };
    }
}
