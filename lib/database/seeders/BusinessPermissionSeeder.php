<?php

namespace Database\Seeders;

class BusinessPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Business management permissions (see business.md).
     */
    public function run(): void
    {
        $this->seedPermissions('System', 'Business', [
            'business.list' => 'Xem danh sách',
            'business.view' => 'Xem chi tiết',
            'business.create' => 'Tạo mới',
            'business.update' => 'Cập nhật',
            'business.delete' => 'Xóa',
        ]);
    }
}
