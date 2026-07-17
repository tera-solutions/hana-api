<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Course '.uniqid(),
            'code' => 'C_'.strtoupper(uniqid()),
            'duration_minutes' => 90,
            'price_per_lesson' => 100000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassId(): int
    {
        return DB::table('edu_classes')->insertGetId([
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'Class '.uniqid(),
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'learning_type' => 'scheduled',
            'status' => 'upcoming',
            'start_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeStudentId(): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeSessionId(int $classId, int $teacherId, string $date, string $start, string $end): int
    {
        return DB::table('edu_sessions')->insertGetId([
            'business_id' => $this->businessId,
            'class_id' => $classId,
            'teacher_id' => $teacherId,
            'session_no' => 1,
            'code' => 'SES_'.strtoupper(uniqid()),
            'name' => 'Session '.uniqid(),
            'session_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeAttendance(int $sessionId): void
    {
        DB::table('edu_attendances')->insert([
            'session_id' => $sessionId,
            'student_id' => $this->makeStudentId(),
            'status' => 'present',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array{0: User, 1: int} [acting user, hr_teachers id] */
    private function actingAsTeacher(float $hourlyRate = 100000): array
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, ['payroll.view']);
        $user = $this->makeUser(false, $roleId, $this->businessId);

        $teacherId = DB::table('hr_teachers')->insertGetId([
            'user_id' => $user->id,
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Teacher '.uniqid(),
            'hourly_rate' => $hourlyRate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsApi($user);

        return [$user, $teacherId];
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/hr/payroll/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/hr/payroll/list')->assertJsonPath('code', 403);
    }

    public function test_teacher_cannot_generate_own_payroll(): void
    {
        $this->actingAsTeacher();

        $this->postJson('/v1/hr/payroll/generate', ['teacher_id' => 1, 'month' => 7, 'year' => 2026])
            ->assertJsonPath('code', 403);
    }

    public function test_generate_computes_salary_from_worked_hours(): void
    {
        [, $teacherId] = $this->actingAsTeacher(100000);
        $classId = $this->makeClassId();

        $s1 = $this->makeSessionId($classId, $teacherId, '2026-07-05', '19:00:00', '20:30:00'); // 1.5h
        $this->makeAttendance($s1);
        $s2 = $this->makeSessionId($classId, $teacherId, '2026-07-12', '19:00:00', '21:00:00'); // 2.0h
        $this->makeAttendance($s2);

        $this->actingAsAdmin($this->businessId);

        $this->postJson('/v1/hr/payroll/generate', [
            'teacher_id' => $teacherId,
            'month' => 7,
            'year' => 2026,
            'bonus' => 200000,
            'penalty' => 50000,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.total_hours', 3.5)
            ->assertJsonPath('data.base_salary', 350000)
            ->assertJsonPath('data.bonus', 200000)
            ->assertJsonPath('data.penalty', 50000)
            ->assertJsonPath('data.total_salary', 500000);
    }

    public function test_regenerate_recomputes_hours_but_keeps_bonus(): void
    {
        [, $teacherId] = $this->actingAsTeacher(100000);
        $classId = $this->makeClassId();
        $s1 = $this->makeSessionId($classId, $teacherId, '2026-07-05', '19:00:00', '20:00:00'); // 1h
        $this->makeAttendance($s1);

        $this->actingAsAdmin($this->businessId);

        $this->postJson('/v1/hr/payroll/generate', [
            'teacher_id' => $teacherId, 'month' => 7, 'year' => 2026, 'bonus' => 300000,
        ])->assertJsonPath('data.base_salary', 100000);

        // Thêm 1 buổi nữa rồi tính lại — KHÔNG gửi bonus → giữ nguyên giá trị cũ.
        $s2 = $this->makeSessionId($classId, $teacherId, '2026-07-19', '19:00:00', '20:00:00'); // 1h
        $this->makeAttendance($s2);

        $this->postJson('/v1/hr/payroll/generate', ['teacher_id' => $teacherId, 'month' => 7, 'year' => 2026])
            ->assertStatus(200)
            ->assertJsonPath('data.total_hours', 2)
            ->assertJsonPath('data.base_salary', 200000)
            ->assertJsonPath('data.bonus', 300000)
            ->assertJsonPath('data.total_salary', 500000);

        $this->assertSame(1, DB::table('hr_payrolls')->where('teacher_id', $teacherId)->count());
    }

    public function test_teacher_lists_only_own_payroll(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $otherTeacherId = DB::table('hr_teachers')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Other',
            'hourly_rate' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hr_payrolls')->insert([
            ['teacher_id' => $teacherId, 'business_id' => $this->businessId, 'month' => 7, 'year' => 2026, 'total_hours' => 1, 'base_salary' => 100000, 'bonus' => 0, 'penalty' => 0, 'total_salary' => 100000, 'created_at' => now(), 'updated_at' => now()],
            ['teacher_id' => $otherTeacherId, 'business_id' => $this->businessId, 'month' => 7, 'year' => 2026, 'total_hours' => 1, 'base_salary' => 100000, 'bonus' => 0, 'penalty' => 0, 'total_salary' => 100000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->getJson('/v1/hr/payroll/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.teacher_id', $teacherId);
    }

    public function test_teacher_cannot_view_another_teachers_payroll_detail(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $otherTeacherId = DB::table('hr_teachers')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Other',
            'hourly_rate' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherPayrollId = DB::table('hr_payrolls')->insertGetId([
            'teacher_id' => $otherTeacherId, 'business_id' => $this->businessId, 'month' => 7, 'year' => 2026,
            'total_hours' => 1, 'base_salary' => 100000, 'bonus' => 0, 'penalty' => 0, 'total_salary' => 100000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->getJson("/v1/hr/payroll/detail/{$otherPayrollId}")->assertJsonPath('code', 403);
    }

    public function test_detail_returns_class_income_breakdown(): void
    {
        [, $teacherId] = $this->actingAsTeacher(150000);
        $classId = $this->makeClassId();
        $s1 = $this->makeSessionId($classId, $teacherId, '2026-07-05', '19:00:00', '21:00:00'); // 2h
        $this->makeAttendance($s1);

        $this->actingAsAdmin($this->businessId);
        $payrollId = $this->postJson('/v1/hr/payroll/generate', [
            'teacher_id' => $teacherId, 'month' => 7, 'year' => 2026,
        ])->json('data.id');

        $response = $this->getJson("/v1/hr/payroll/detail/{$payrollId}")
            ->assertStatus(200)
            ->assertJsonPath('data.payroll.id', $payrollId)
            ->assertJsonPath('data.class_income.0.hours', 2)
            ->assertJsonPath('data.class_income.0.unit_price', 150000)
            ->assertJsonPath('data.class_income.0.total', 300000);

        $response->assertJsonPath('data.teacher.hourly_rate', 150000);
    }
}
