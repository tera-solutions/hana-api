<?php

namespace Database\Seeders;

class TimetablePermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Timetable management permissions (timetable-management.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Timetable', [
            'timetable.list' => 'Xem danh sách thời khóa biểu',
            'timetable.view' => 'Xem chi tiết / lịch thời khóa biểu',
            'timetable.create' => 'Tạo thời khóa biểu',
            'timetable.update' => 'Cập nhật thời khóa biểu',
            'timetable.delete' => 'Xóa thời khóa biểu',
        ]);
    }
}
