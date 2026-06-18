<?php

namespace Database\Seeders;

class MaterialPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Material management permissions (material.md §2).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Material', [
            'material.list' => 'Xem danh sách',
            'material.view' => 'Xem chi tiết',
            'material.create' => 'Tải lên tài liệu',
            'material.update' => 'Cập nhật tài liệu',
            'material.delete' => 'Xóa tài liệu',
            'material.download' => 'Download tài liệu',
            'material.manage' => 'Quản lý thư viện',
        ]);
    }
}
