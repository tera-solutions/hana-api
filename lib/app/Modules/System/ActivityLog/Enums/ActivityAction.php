<?php

namespace App\Modules\System\ActivityLog\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Action taxonomy (spec 028 §XI).
 */
enum ActivityAction: string implements HasLabel
{
    use ProvidesOptions;

    case Login = 'login';
    case Logout = 'logout';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Restore = 'restore';
    case Approve = 'approve';
    case Reject = 'reject';
    case Assign = 'assign';
    case Unassign = 'unassign';
    case Pay = 'pay';
    case Refund = 'refund';
    case Import = 'import';
    case Export = 'export';
    case SendEmail = 'send_email';
    case SendSms = 'send_sms';
    case Download = 'download';
    case Upload = 'upload';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Đăng nhập',
            self::Logout => 'Đăng xuất',
            self::Create => 'Tạo mới',
            self::Update => 'Cập nhật',
            self::Delete => 'Xóa',
            self::Restore => 'Khôi phục',
            self::Approve => 'Phê duyệt',
            self::Reject => 'Từ chối',
            self::Assign => 'Gán',
            self::Unassign => 'Hủy gán',
            self::Pay => 'Thanh toán',
            self::Refund => 'Hoàn tiền',
            self::Import => 'Nhập dữ liệu',
            self::Export => 'Xuất dữ liệu',
            self::SendEmail => 'Gửi email',
            self::SendSms => 'Gửi SMS',
            self::Download => 'Tải xuống',
            self::Upload => 'Tải lên',
        };
    }
}
