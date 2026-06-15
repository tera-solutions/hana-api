<?php

namespace App\Modules\Finance\Invoice\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * The counterparty an invoice is raised against. RECEIVABLE invoices target
 * student/parent/company; PAYABLE invoices target teacher/staff/supplier/landlord/partner.
 */
enum PartnerType: string implements HasLabel
{
    use ProvidesOptions;

    case Student = 'student';
    case Parent = 'parent';
    case Company = 'company';
    case Teacher = 'teacher';
    case Staff = 'staff';
    case Supplier = 'supplier';
    case Landlord = 'landlord';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Học viên',
            self::Parent => 'Phụ huynh',
            self::Company => 'Công ty',
            self::Teacher => 'Giáo viên',
            self::Staff => 'Nhân viên',
            self::Supplier => 'Nhà cung cấp',
            self::Landlord => 'Chủ mặt bằng',
            self::Partner => 'Đối tác',
        };
    }
}
