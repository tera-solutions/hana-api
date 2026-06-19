<?php

namespace Database\Seeders;

class QuestionPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Question bank permissions (question.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('Education', 'Question', [
            'question.list' => 'Xem danh sách',
            'question.view' => 'Xem câu hỏi',
            'question.create' => 'Tạo câu hỏi',
            'question.update' => 'Cập nhật câu hỏi',
            'question.delete' => 'Xóa câu hỏi',
            'question.import' => 'Import câu hỏi',
            'question.approve' => 'Duyệt câu hỏi',
            'question.generate_exam' => 'Sinh đề thi',
        ]);
    }
}
