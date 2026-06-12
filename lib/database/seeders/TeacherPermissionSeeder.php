<?php

namespace Database\Seeders;

class TeacherPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Teacher management permissions.
     */
    public function run(): void
    {
        $this->seedPermissions('HR', 'Teacher', [
            'teacher.list' => 'Xem danh sách',
            'teacher.view' => 'Xem chi tiết',
            'teacher.create' => 'Tạo mới',
            'teacher.update' => 'Cập nhật',
            'teacher.suspend' => 'Tạm ngừng',
            'teacher.restore' => 'Khôi phục',
            'teacher.resign' => 'Nghỉ việc',
        ]);
    }
}
