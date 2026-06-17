<?php

namespace Database\Seeders;

class LessonPlanPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Lesson Plan management permissions.
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'LessonPlan', [
            'lesson_plan.list' => 'Xem danh sách',
            'lesson_plan.view' => 'Xem chi tiết',
            'lesson_plan.create' => 'Tạo mới',
            'lesson_plan.update' => 'Cập nhật',
            'lesson_plan.delete' => 'Xóa',
            'lesson_plan.publish' => 'Xuất bản',
            'lesson_plan.clone' => 'Sao chép',
            'lesson_plan.version' => 'Quản lý phiên bản',
        ]);
    }
}
