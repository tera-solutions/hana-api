<?php

namespace Database\Seeders;

class EvaluationPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Evaluation management permissions (evaluation.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Evaluation', [
            'evaluation.list' => 'Xem danh sách đánh giá',
            'evaluation.view' => 'Xem chi tiết đánh giá',
            'evaluation.create' => 'Tạo đánh giá',
            'evaluation.update' => 'Cập nhật đánh giá',
            'evaluation.delete' => 'Xóa đánh giá',
            'evaluation.approve' => 'Duyệt / khóa đánh giá',
        ]);
    }
}
