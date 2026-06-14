<?php

namespace App\Modules\Education\ClassRoom\Enums;

use App\Enums\Concerns\HasBadge;
use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;
use App\Enums\Concerns\ResolvesBadge;
use App\Enums\Shared\BadgeColor;

enum ClassStudentStatus: string implements HasBadge, HasLabel
{
    use ProvidesOptions;
    use ResolvesBadge;

    case Active = 'active';
    case Reserved = 'reserved';
    case Completed = 'completed';
    case Dropped = 'dropped';
    case TransferredOut = 'transferred_out';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Đang học',
            self::Reserved => 'Bảo lưu',
            self::Completed => 'Hoàn thành',
            self::Dropped => 'Đã nghỉ',
            self::TransferredOut => 'Đã chuyển lớp',
        };
    }

    public function badge(): BadgeColor
    {
        return match ($this) {
            self::Active => BadgeColor::Info,
            self::Reserved => BadgeColor::Warning,
            self::Completed => BadgeColor::Success,
            self::Dropped => BadgeColor::Neutral,
            self::TransferredOut => BadgeColor::Warning,
        };
    }
}
