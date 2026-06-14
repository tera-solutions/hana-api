<?php

namespace Database\Seeders;

class EnrollmentPermissionSeeder extends PermissionSeeder
{
    public function run(): void
    {
        $this->seedPermissions('Education', 'Enrollment', [
            'enrollment.list' => 'Xem danh sách',
            'enrollment.view' => 'Xem chi tiết',
            'enrollment.create' => 'Tạo mới',
            'enrollment.update' => 'Cập nhật',
            'enrollment.suspend' => 'Bảo lưu',
            'enrollment.transfer' => 'Chuyển lớp',
            'enrollment.refund' => 'Hoàn phí',
            'enrollment.cancel' => 'Hủy ghi danh',
        ]);
    }
}
