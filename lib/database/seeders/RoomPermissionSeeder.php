<?php

namespace Database\Seeders;

class RoomPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Room management permissions.
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Room', [
            'room.list' => 'Xem danh sách',
            'room.view' => 'Xem chi tiết',
            'room.create' => 'Tạo mới',
            'room.update' => 'Cập nhật',
            'room.suspend' => 'Ngừng sử dụng',
            'room.restore' => 'Khôi phục',
        ]);
    }
}
