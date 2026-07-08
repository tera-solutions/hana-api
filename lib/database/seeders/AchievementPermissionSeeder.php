<?php

namespace Database\Seeders;

class AchievementPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Achievement / Teacher Review permissions.
     */
    public function run(): void
    {
        $this->seedPermissions('HR', 'Achievement', [
            'achievement.view' => 'Xem thành tích',
            'teacher_review.create' => 'Gửi đánh giá giáo viên',
        ]);
    }
}
