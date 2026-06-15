<?php

namespace Database\Seeders;

class InvoicePermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Finance Invoice management permissions (invoice.md §XIV).
     */
    public function run(): void
    {
        $this->seedPermissions('Finance', 'Invoice', [
            'fin_invoice.list' => 'Xem danh sách',
            'fin_invoice.view' => 'Xem chi tiết',
            'fin_invoice.create' => 'Tạo mới',
            'fin_invoice.update' => 'Cập nhật',
            'fin_invoice.approve' => 'Duyệt / Từ chối',
            'fin_invoice.cancel' => 'Hủy hóa đơn',
            'fin_invoice.refund' => 'Hoàn tiền',
            'fin_invoice.pay' => 'Ghi nhận thanh toán',
        ]);
    }
}
