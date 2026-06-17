<?php

namespace Database\Seeders;

class LessonPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Lesson management permissions.
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Lesson', [
            'lesson.list' => 'Xem danh sách',
            'lesson.view' => 'Xem chi tiết',
            'lesson.update' => 'Cập nhật',
            'lesson.reschedule' => 'Đổi lịch',
            'lesson.cancel' => 'Hủy',
            'lesson.unlock' => 'Mở khóa',
        ]);
    }
}
