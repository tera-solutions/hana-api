<?php

namespace Database\Seeders;

class ParentStudentPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Parent-Student relationship permissions.
     */
    public function run(): void
    {
        $this->seedPermissions('CRM', 'ParentStudent', [
            'parent_student.list' => 'Xem danh sách',
            'parent_student.view' => 'Xem chi tiết',
            'parent_student.create' => 'Thêm quan hệ',
            'parent_student.update' => 'Cập nhật quan hệ',
            'parent_student.delete' => 'Xóa quan hệ',
        ]);
    }
}
