<?php

namespace App\Modules\Education\ClassRoom\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum ClassStudentStatus: string implements HasLabel
{
    use ProvidesOptions;

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
}
