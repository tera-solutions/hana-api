<?php

namespace Database\Seeders;

class ExamPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Exam management permissions (exam.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Exam', [
            'exam.list' => 'Xem danh sách',
            'exam.view' => 'Xem bài kiểm tra',
            'exam.create' => 'Tạo bài kiểm tra',
            'exam.update' => 'Cập nhật bài kiểm tra',
            'exam.delete' => 'Xóa bài kiểm tra',
            'exam.schedule' => 'Tổ chức thi',
            'exam.grade' => 'Chấm thi',
            'exam.publish' => 'Công bố kết quả',
            'exam.promote' => 'Xét lên cấp',
        ]);
    }
}
