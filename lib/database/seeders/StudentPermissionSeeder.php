<?php

namespace Database\Seeders;

class StudentPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Student management permissions.
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Student', [
            'student.list' => 'Xem danh sách',
            'student.view' => 'Xem chi tiết',
            'student.create' => 'Tạo mới',
            'student.update' => 'Cập nhật',
            'student.suspend' => 'Ngừng học',
            'student.restore' => 'Khôi phục',
            'student.delete' => 'Xóa',
        ]);
    }
}
