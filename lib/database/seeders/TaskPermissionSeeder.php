<?php

namespace Database\Seeders;

class TaskPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Task management permissions (task-management.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('System', 'Task', [
            'task.list' => 'Xem danh sách công việc',
            'task.view' => 'Xem chi tiết công việc',
            'task.create' => 'Tạo công việc',
            'task.update' => 'Cập nhật công việc',
            'task.delete' => 'Xóa công việc',
        ]);
    }
}
