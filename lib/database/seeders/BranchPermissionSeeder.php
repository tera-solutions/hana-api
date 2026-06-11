<?php

namespace Database\Seeders;

class BranchPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Branch management permissions (see branch.md).
     */
    public function run(): void
    {
        $this->seedPermissions('System', 'Branch', [
            'branch.list' => 'Xem danh sách',
            'branch.view' => 'Xem chi tiết',
            'branch.create' => 'Tạo mới',
            'branch.update' => 'Cập nhật',
            'branch.delete' => 'Xóa',
        ]);
    }
}
