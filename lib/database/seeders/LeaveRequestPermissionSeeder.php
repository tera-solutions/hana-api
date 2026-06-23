<?php

namespace Database\Seeders;

class LeaveRequestPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Leave Request management permissions (leave-request.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'LeaveRequest', [
            'leave.list' => 'Xem danh sách',
            'leave.view' => 'Xem chi tiết',
            'leave.create' => 'Tạo đơn nghỉ',
            'leave.update' => 'Cập nhật đơn nghỉ',
            'leave.approve' => 'Duyệt đơn nghỉ',
            'leave.reject' => 'Từ chối đơn nghỉ',
            'leave.cancel' => 'Hủy đơn nghỉ',
            'leave.makeup' => 'Quản lý học bù',
        ]);
    }
}
