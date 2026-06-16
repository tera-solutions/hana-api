<?php

namespace App\Modules\Finance\Payment\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\ProvidesOptions;

/**
 * Payment categories (payment.md §IV). IN types collect money; OUT types disburse it.
 */
enum PaymentType: string implements HasLabel
{
    use ProvidesOptions;

    // Thu (IN)
    case TuitionPayment = 'tuition_payment';
    case ServicePayment = 'service_payment';
    case DebtCollection = 'debt_collection';
    case OtherIncome = 'other_income';

    // Chi (OUT)
    case SalaryPayment = 'salary_payment';
    case TeacherPayment = 'teacher_payment';
    case SupplierPayment = 'supplier_payment';
    case RentPayment = 'rent_payment';
    case MarketingPayment = 'marketing_payment';
    case UtilityPayment = 'utility_payment';
    case OtherExpense = 'other_expense';

    public function label(): string
    {
        return match ($this) {
            self::TuitionPayment => 'Thu học phí',
            self::ServicePayment => 'Thu phí dịch vụ',
            self::DebtCollection => 'Thu công nợ',
            self::OtherIncome => 'Thu khác',
            self::SalaryPayment => 'Chi lương nhân viên',
            self::TeacherPayment => 'Chi lương giáo viên',
            self::SupplierPayment => 'Thanh toán nhà cung cấp',
            self::RentPayment => 'Chi thuê mặt bằng',
            self::MarketingPayment => 'Chi marketing',
            self::UtilityPayment => 'Chi điện nước',
            self::OtherExpense => 'Chi khác',
        };
    }
}
