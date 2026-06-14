<?php

namespace Database\Seeders;

class ClassSessionPermissionSeeder extends PermissionSeeder
{
    public function run(): void
    {
        $this->seedPermissions('Education', 'ClassSession', [
            'session.list' => 'Xem danh sách',
            'session.view' => 'Xem chi tiết',
            'session.create' => 'Tạo mới',
            'session.generate' => 'Sinh hàng loạt',
            'session.update' => 'Cập nhật',
            'session.cancel' => 'Hủy buổi học',
            'session.delete' => 'Xóa buổi học',
        ]);
    }
}
