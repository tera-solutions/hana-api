<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds demo portal login accounts only — teacher, parent and student users —
 * each with the minimal profile row (hr_teachers / crm_parents / edu_students)
 * their role's scope helper needs to resolve correctly. No courses, classes,
 * lessons, billing or any other business data is seeded.
 *
 *   Teacher portal:  giaovien  / 12345678  (Cô Hà — GV001)
 *                    giaovien2 / 12345678  (Thầy Minh — GV002)
 *   Parent portal:   phuhuynh1 / 12345678  (Chị Lan)
 *                    phuhuynh2 / 12345678  (Anh Tuấn)
 *   Student portal:  hocvien1..hocvien4 / 12345678 (Bé An, Bình, Chi, Dũng)
 *
 * Portal users are deliberately NOT admins, so permission-gated (non-admin) access applies.
 *
 * Runs after BusinessAndUserSeeder + permission seeders (reuses the first
 * business and the TEACHER_ROLE / STUDENT_ROLE / PARENT_ROLE roles). Standalone:
 *   php artisan db:seed --class="Database\Seeders\DemoDataSeeder"
 */
class DemoDataSeeder extends Seeder
{
    private const PASSWORD = '12345678';

    private int $businessId;

    public function run(): void
    {
        $this->businessId = (int) DB::table('sys_business')->orderBy('id')->value('id');

        if (! $this->businessId) {
            $this->command?->error('DemoDataSeeder: run BusinessAndUserSeeder first.');

            return;
        }

        $fallbackRoleId = (int) DB::table('sys_roles')->orderBy('id')->value('id');
        $roleId = fn (string $code): int => (int) (DB::table('sys_roles')->where('code', $code)->value('id') ?: $fallbackRoleId);

        $teacherRoleId = $roleId('TEACHER_ROLE');
        $studentRoleId = $roleId('STUDENT_ROLE');
        $parentRoleId = $roleId('PARENT_ROLE');

        DB::transaction(function () use ($teacherRoleId, $studentRoleId, $parentRoleId) {
            $this->teachers($teacherRoleId);
            $this->parents($parentRoleId);
            $this->students($studentRoleId);
        });

        $this->command?->info('DemoDataSeeder: demo portal users seeded.');
        $this->command?->table(
            ['Portal', 'Username', 'Password'],
            [
                ['Teacher (chủ nhiệm)', 'giaovien', self::PASSWORD],
                ['Teacher (trợ giảng)', 'giaovien2', self::PASSWORD],
                ['Parent (2 con)', 'phuhuynh1', self::PASSWORD],
                ['Parent (1 con)', 'phuhuynh2', self::PASSWORD],
                ['Student', 'hocvien1..hocvien4', self::PASSWORD],
            ],
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function ts(): array
    {
        return ['created_at' => now(), 'updated_at' => now()];
    }

    /** A portal login (non-admin) so scoping and per-role permissions apply. */
    private function portalUser(string $username, string $fullName, string $email, int $roleId): int
    {
        return DB::table('users')->insertGetId([
            'full_name' => $fullName,
            'avatar' => 'https://api.anhnguhana.com/assets/avatar/01.png',
            'username' => $username,
            'email' => $email,
            'status' => 'active',
            'code' => 'U_'.strtoupper($username),
            'is_active' => true,
            'is_admin' => false,
            'password' => Hash::make(self::PASSWORD),
            'business_id' => $this->businessId,
            'role_id' => $roleId,
        ] + $this->ts());
    }

    // ── Portal users ─────────────────────────────────────────────────────────

    private function teachers(int $teacherRoleId): void
    {
        $rows = [
            ['GV001', 'Cô Hà', 'giaovien', 'gv.ha@hana.edu.vn'],
            ['GV002', 'Thầy Minh', 'giaovien2', 'gv.minh@hana.edu.vn'],
        ];

        foreach ($rows as [$code, $name, $username, $email]) {
            $userId = $this->portalUser($username, $name, $email, $teacherRoleId);

            DB::table('hr_teachers')->insert([
                'user_id' => $userId,
                'business_id' => $this->businessId,
                'code' => $code,
                'full_name' => $name,
                'email' => $email,
                'status' => 'active',
            ] + $this->ts());
        }
    }

    private function parents(int $parentRoleId): void
    {
        $rows = [
            ['PAR0001', 'Chị Lan', 'phuhuynh1', 'ph.lan@example.com'],
            ['PAR0002', 'Anh Tuấn', 'phuhuynh2', 'ph.tuan@example.com'],
        ];

        foreach ($rows as [$code, $name, $username, $email]) {
            $userId = $this->portalUser($username, $name, $email, $parentRoleId);

            DB::table('crm_parents')->insert([
                'user_id' => $userId,
                'business_id' => $this->businessId,
                'code' => $code,
                'name' => $name,
                'email' => $email,
                'status' => 'active',
            ] + $this->ts());
        }
    }

    private function students(int $studentRoleId): void
    {
        $rows = [
            ['STU0001', 'Bé An', 'hocvien1'],
            ['STU0002', 'Bé Bình', 'hocvien2'],
            ['STU0003', 'Bé Chi', 'hocvien3'],
            ['STU0004', 'Bé Dũng', 'hocvien4'],
        ];

        foreach ($rows as [$code, $name, $username]) {
            $email = strtolower($username).'@hana.edu.vn';
            $userId = $this->portalUser($username, $name, $email, $studentRoleId);

            DB::table('edu_students')->insert([
                'user_id' => $userId,
                'business_id' => $this->businessId,
                'code' => $code,
                'name' => $name,
                'email' => $email,
                'status' => 'studying',
            ] + $this->ts());
        }
    }
}
