<?php

namespace Database\Seeders;

class AccountPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Finance Account (fund / quỹ) management permissions (payment.md §VI).
     */
    public function run(): void
    {
        $this->seedPermissions('Finance', 'Account', [
            'fin_account.list' => 'Xem danh sách',
            'fin_account.view' => 'Xem chi tiết',
            'fin_account.create' => 'Tạo mới',
            'fin_account.update' => 'Cập nhật',
            'fin_account.suspend' => 'Ngừng quỹ',
            'fin_account.restore' => 'Khôi phục quỹ',
        ]);
    }
}
