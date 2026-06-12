<?php

namespace Database\Seeders;

class CoursePermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Course management permissions.
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Course', [
            'course.list' => 'Xem danh sách',
            'course.view' => 'Xem chi tiết',
            'course.create' => 'Tạo mới',
            'course.update' => 'Cập nhật',
            'course.suspend' => 'Ngừng hoạt động',
            'course.restore' => 'Khôi phục',
        ]);
    }
}
