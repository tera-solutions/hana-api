<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                // Internal trial plan that backs self-service signups. Hidden from
                // the purchasable package listing via is_active=false.
                'package_code' => 'PKG-TRIAL',
                'name' => 'Gói Dùng thử',
                'description' => 'Trải nghiệm miễn phí trong 14 ngày.',
                'price' => 0,
                'billing_cycle' => 'month',
                'features' => ['Quản lý lớp học', 'Điểm danh học viên', 'Giáo án cơ bản'],
                'feature_keys' => [],
                'limits' => ['students' => 20, 'classes' => 3, 'teachers' => 2, 'branches' => 1, 'parents' => 20],
                'badge' => 'Dùng thử',
                'is_active' => false,
                'sort_order' => 0,
            ],
            [
                'package_code' => 'PKG-BASIC',
                'name' => 'Gói Giáo viên Cơ bản',
                'description' => 'Dành cho giáo viên mới bắt đầu.',
                'price' => 149000,
                'billing_cycle' => 'month',
                'features' => ['Quản lý lớp học', 'Điểm danh học viên', 'Giáo án & tài liệu cơ bản'],
                'feature_keys' => [],
                'limits' => ['students' => 50, 'classes' => 5, 'teachers' => 3, 'branches' => 1, 'parents' => 50],
                'badge' => null,
                'sort_order' => 1,
            ],
            [
                'package_code' => 'PKG-ADVANCED',
                'name' => 'Gói Giáo viên Nâng cao',
                'description' => 'Quản lý không giới hạn lớp học, học viên.',
                'price' => 299000,
                'billing_cycle' => 'month',
                'features' => [
                    'Quản lý lớp học không giới hạn',
                    'Lịch dạy & điểm danh học viên',
                    'Giáo án & tài liệu không giới hạn',
                    'Bài tập & chấm bài',
                    'Nhắn tin & thông báo',
                    'Báo cáo nâng cao',
                ],
                'feature_keys' => ['assignments', 'messaging', 'advanced_reports'],
                'limits' => ['students' => 500, 'classes' => 50, 'teachers' => 20, 'branches' => 3, 'parents' => 500],
                'badge' => null,
                'sort_order' => 2,
            ],
            [
                'package_code' => 'PKG-PRO',
                'name' => 'Gói Giáo viên Toàn diện',
                'description' => 'Tất cả tính năng nâng cao dành cho giáo viên chuyên nghiệp.',
                'price' => 499000,
                'billing_cycle' => 'month',
                'features' => [
                    'Tất cả quyền lợi của gói Nâng cao',
                    'Không giới hạn học viên',
                    'Báo cáo chuyên sâu & phân tích AI',
                    'Tùy chỉnh thương hiệu cá nhân',
                    'Hỗ trợ ưu tiên 24/7',
                ],
                'feature_keys' => ['assignments', 'messaging', 'advanced_reports', 'ai_analytics', 'branding'],
                // null caps = unlimited (Toàn diện: không giới hạn).
                'limits' => ['students' => null, 'classes' => null, 'teachers' => null, 'branches' => null, 'parents' => null],
                'badge' => 'Phổ biến',
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            DB::table('sys_packages')->updateOrInsert(
                ['package_code' => $package['package_code']],
                [
                    'name' => $package['name'],
                    'description' => $package['description'],
                    'price' => $package['price'],
                    'billing_cycle' => $package['billing_cycle'],
                    'features' => json_encode($package['features']),
                    'feature_keys' => json_encode($package['feature_keys'] ?? []),
                    'limits' => json_encode($package['limits']),
                    'badge' => $package['badge'],
                    'is_active' => $package['is_active'] ?? true,
                    'sort_order' => $package['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
