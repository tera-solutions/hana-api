<?php

namespace Database\Seeders;

class LevelPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Level master permissions (student-level.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Level', [
            'level.list' => 'Xem danh sách cấp độ',
            'level.view' => 'Xem chi tiết cấp độ',
            'level.create' => 'Tạo cấp độ',
            'level.update' => 'Cập nhật cấp độ',
            'level.suspend' => 'Ngừng sử dụng cấp độ',
            'level.restore' => 'Khôi phục cấp độ',
        ]);
    }
}
