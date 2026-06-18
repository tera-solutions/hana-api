<?php

namespace Database\Seeders;

class ActivityLogPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Activity Log permissions (spec 028 §II). Read-only feature.
     */
    public function run(): void
    {
        $this->seedPermissions('System', 'ActivityLog', [
            'activity_log.list' => 'Xem danh sách nhật ký',
            'activity_log.view' => 'Xem chi tiết nhật ký',
            'activity_log.export' => 'Xuất nhật ký',
        ]);
    }
}
