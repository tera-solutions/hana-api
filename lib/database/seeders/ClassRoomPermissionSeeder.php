<?php

namespace Database\Seeders;

class ClassRoomPermissionSeeder extends PermissionSeeder
{
    public function run(): void
    {
        $this->seedPermissions('Education', 'ClassRoom', [
            'class.list' => 'Xem danh sách',
            'class.view' => 'Xem chi tiết',
            'class.create' => 'Tạo mới',
            'class.update' => 'Cập nhật',
            'class.suspend' => 'Tạm ngừng',
            'class.restore' => 'Khôi phục',
        ]);
    }
}
