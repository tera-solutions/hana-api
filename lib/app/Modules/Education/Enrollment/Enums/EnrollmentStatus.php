<?php

namespace App\Modules\Education\Enrollment\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

enum EnrollmentStatus: string implements HasLabel
{
    use ProvidesOptions;

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
}
