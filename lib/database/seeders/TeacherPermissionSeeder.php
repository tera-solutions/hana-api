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
            'teacher.delete' => 'Xóa',
        ]);
    }
}
