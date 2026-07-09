<?php

namespace Database\Seeders;

class AttendancePermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Attendance permissions (class-session.md §13, §15).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Attendance', [
            'attendance.list' => 'Xem danh sách chuyên cần',
            'attendance.view' => 'Xem chi tiết chuyên cần',
            'attendance.create' => 'Điểm danh học viên',
            'attendance.update' => 'Cập nhật điểm danh',
            'attendance.export' => 'Xuất báo cáo chuyên cần',
        ]);
    }
}
