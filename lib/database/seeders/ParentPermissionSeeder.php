<?php

namespace Database\Seeders;

class ParentPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Parent management permissions.
     */
    public function run(): void
    {
        $this->seedPermissions('CRM', 'Parent', [
            'parent.list' => 'Xem danh sách',
            'parent.view' => 'Xem chi tiết',
            'parent.create' => 'Tạo mới',
            'parent.update' => 'Cập nhật',
            'parent.suspend' => 'Tạm ngừng',
            'parent.restore' => 'Khôi phục',
        ]);
    }
}
