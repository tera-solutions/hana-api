<?php

namespace Database\Seeders;

class UserPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the User management permissions (see user.md).
     */
    public function run(): void
    {
        $this->seedPermissions('System', 'User', [
            'user.list' => 'Xem danh sách',
            'user.view' => 'Xem chi tiết',
            'user.create' => 'Tạo mới',
            'user.update' => 'Cập nhật',
            'user.delete' => 'Xóa',
            'user.activate' => 'Kích hoạt tài khoản',
            'user.deactivate' => 'Vô hiệu hóa tài khoản',
            'user.reset_password' => 'Đặt lại mật khẩu',
            'user.unlock' => 'Mở khóa tài khoản',
            'user.assign_role' => 'Gán vai trò',
        ]);
    }
}
