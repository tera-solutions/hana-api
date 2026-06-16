<?php

namespace Database\Seeders;

class PaymentPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Finance Payment management permissions (payment.md §XV).
     */
    public function run(): void
    {
        $this->seedPermissions('Finance', 'Payment', [
            'fin_payment.list' => 'Xem danh sách',
            'fin_payment.view' => 'Xem chi tiết',
            'fin_payment.create' => 'Tạo mới',
            'fin_payment.update' => 'Cập nhật',
            'fin_payment.confirm' => 'Xác nhận',
            'fin_payment.cancel' => 'Hủy giao dịch',
            'fin_payment.reverse' => 'Đảo giao dịch',
            'fin_payment.refund' => 'Hoàn tiền',
        ]);
    }
}
