<?php

namespace App\Modules\System\ActivityLog\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Module taxonomy used to group/filter logs (spec 028 §IV).
 */
enum ActivityModule: string implements HasLabel
{
    use ProvidesOptions;

    case System = 'system';
    case Crm = 'crm';
    case Education = 'education';
    case Finance = 'finance';
    case Wallet = 'wallet';
    case Hr = 'hr';
    case Notification = 'notification';

    public function label(): string
    {
        return match ($this) {
            self::System => 'Hệ thống',
            self::Crm => 'CRM',
            self::Education => 'Đào tạo',
            self::Finance => 'Tài chính',
            self::Wallet => 'Ví',
            self::Hr => 'Nhân sự',
            self::Notification => 'Thông báo',
        };
    }
}
