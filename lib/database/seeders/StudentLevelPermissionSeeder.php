<?php

namespace Database\Seeders;

class StudentLevelPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Student Level permissions (student-level.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'StudentLevel', [
            'student_level.view' => 'Xem cấp độ học viên',
            'student_level.placement' => 'Đánh giá đầu vào',
            'student_level.promote' => 'Xét lên cấp',
            'student_level.adjust' => 'Điều chỉnh cấp độ',
            'student_level.history' => 'Xem lịch sử cấp độ',
        ]);
    }
}
