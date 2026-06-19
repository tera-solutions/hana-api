<?php

namespace Database\Seeders;

class PromotionPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Promotion management permissions (promotion.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('Finance', 'Promotion', [
            'promotion.list' => 'Xem danh sách',
            'promotion.view' => 'Xem chi tiết',
            'promotion.create' => 'Tạo khuyến mãi',
            'promotion.update' => 'Cập nhật khuyến mãi',
            'promotion.stop' => 'Dừng khuyến mãi',
            'promotion.approve' => 'Duyệt / kích hoạt khuyến mãi',
            'promotion.apply' => 'Áp dụng khuyến mãi',
        ]);
    }
}
