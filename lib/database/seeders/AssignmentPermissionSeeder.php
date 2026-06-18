<?php

namespace Database\Seeders;

class AssignmentPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Assignment management permissions (assignment.md §2).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Assignment', [
            'assignment.list' => 'Xem danh sách',
            'assignment.view' => 'Xem bài tập',
            'assignment.create' => 'Tạo bài tập',
            'assignment.update' => 'Cập nhật bài tập',
            'assignment.delete' => 'Xóa bài tập',
            'assignment.assign' => 'Giao bài tập',
            'assignment.grade' => 'Chấm bài tập',
            'assignment.result' => 'Xem kết quả',
        ]);
    }
}
