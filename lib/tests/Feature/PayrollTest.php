<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_list_auto_generates_payroll_for_worked_months_without_manual_trigger(): void
    {
        [, $teacherId] = $this->actingAsTeacher(100000);
        $classId = $this->makeClassId();

        // Buổi dạy ở 2 tháng khác nhau, chưa ai gọi POST /payroll/generate.
        $s1 = $this->makeSessionId($classId, $teacherId, '2026-06-05', '19:00:00', '20:00:00'); // 1h, tháng 6
        $this->makeAttendance($s1);
        $s2 = $this->makeSessionId($classId, $teacherId, '2026-07-12', '19:00:00', '21:00:00'); // 2h, tháng 7
        $this->makeAttendance($s2);

        $this->getJson('/v1/hr/payroll/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.items.0.month', 7)
            ->assertJsonPath('data.items.0.total_hours', 2)
            ->assertJsonPath('data.items.0.base_salary', 200000)
            ->assertJsonPath('data.items.1.month', 6)
            ->assertJsonPath('data.items.1.total_hours', 1)
            ->assertJsonPath('data.items.1.base_salary', 100000);
    }

    public function test_list_backfill_is_idempotent_and_keeps_existing_bonus(): void
    {
        [$user, $teacherId] = $this->actingAsTeacher(100000);
        $classId = $this->makeClassId();
        $s1 = $this->makeSessionId($classId, $teacherId, '2026-07-05', '19:00:00', '20:00:00');
        $this->makeAttendance($s1);

        // First list() call auto-generates July; an admin manually adds a bonus afterwards.
        $this->getJson('/v1/hr/payroll/list')->assertStatus(200);
        DB::table('hr_payrolls')
            ->where('teacher_id', $teacherId)->where('month', 7)->where('year', 2026)
            ->update(['bonus' => 50000, 'total_salary' => 150000]);

        // Calling list() again must not clobber the manually-set bonus.
        $this->getJson('/v1/hr/payroll/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.bonus', 50000)
            ->assertJsonPath('data.items.0.total_salary', 150000);
    }

    public function test_teacher_cannot_generate_own_payroll(): void
    {
        $this->actingAsTeacher();

        $this->postJson('/v1/hr/payroll/generate', ['teacher_id' => 1, 'month' => 7, 'year' => 2026])
            ->assertJsonPath('code', 403);
    }

    /** @return array{0: User, 1: int} [acting user, hr_teachers id] */
    private function actingAsTeacherWithGenerate(float $hourlyRate = 100000): array
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, ['payroll.view', 'payroll.generate']);
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

    public function test_teacher_with_generate_permission_can_generate_own_payroll(): void
    {
        [, $teacherId] = $this->actingAsTeacherWithGenerate(100000);
        $classId = $this->makeClassId();
        $s1 = $this->makeSessionId($classId, $teacherId, '2026-07-05', '19:00:00', '20:30:00'); // 1.5h
        $this->makeAttendance($s1);

        $this->postJson('/v1/hr/payroll/generate', [
            'teacher_id' => $teacherId, 'month' => 7, 'year' => 2026,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.total_hours', 1.5)
            ->assertJsonPath('data.base_salary', 150000);
    }

    public function test_teacher_with_generate_permission_cannot_generate_another_teachers_payroll(): void
    {
        $this->actingAsTeacherWithGenerate();
        $otherTeacherId = DB::table('hr_teachers')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Other',
            'hourly_rate' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/v1/hr/payroll/generate', [
            'teacher_id' => $otherTeacherId, 'month' => 7, 'year' => 2026,
        ])->assertJsonPath('code', 403);
    }

    public function test_teacher_with_generate_permission_cannot_set_own_bonus_or_penalty(): void
    {
        [, $teacherId] = $this->actingAsTeacherWithGenerate(100000);
        $classId = $this->makeClassId();
        $s1 = $this->makeSessionId($classId, $teacherId, '2026-07-05', '19:00:00', '20:00:00'); // 1h
        $this->makeAttendance($s1);

        $this->postJson('/v1/hr/payroll/generate', [
            'teacher_id' => $teacherId, 'month' => 7, 'year' => 2026,
            'bonus' => 999999, 'penalty' => 0,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.bonus', 0)
            ->assertJsonPath('data.total_salary', 100000);
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

    public function test_admin_can_list_another_teachers_payroll_via_teacher_id_param(): void
    {
        [, $teacherId] = $this->actingAsTeacher();

        DB::table('hr_payrolls')->insert([
            'teacher_id' => $teacherId, 'business_id' => $this->businessId, 'month' => 7, 'year' => 2026,
            'total_hours' => 1, 'base_salary' => 100000, 'bonus' => 0, 'penalty' => 0, 'total_salary' => 100000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAsAdmin($this->businessId);

        $this->getJson("/v1/hr/payroll/list?teacher_id={$teacherId}")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.teacher_id', $teacherId);
    }

    public function test_admin_can_pay_payroll_and_credits_teacher_wallet(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $payrollId = DB::table('hr_payrolls')->insertGetId([
            'teacher_id' => $teacherId, 'business_id' => $this->businessId, 'month' => 7, 'year' => 2026,
            'total_hours' => 10, 'base_salary' => 1000000, 'bonus' => 0, 'penalty' => 0, 'total_salary' => 1000000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAsAdmin($this->businessId);

        $this->postJson("/v1/hr/payroll/pay/{$payrollId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('hr_payrolls', ['id' => $payrollId, 'status' => 'paid']);
        $this->assertNotNull(DB::table('hr_payrolls')->where('id', $payrollId)->value('paid_at'));

        $wallet = DB::table('fin_wallets')->where('owner_type', 'teacher')->where('owner_id', $teacherId)->first();
        $this->assertNotNull($wallet);
        $this->assertSame(1000000.0, (float) $wallet->available_balance);

        $this->assertDatabaseHas('fin_wallet_transactions', [
            'wallet_id' => $wallet->id,
            'transaction_type' => 'salary',
            'reference_type' => 'payroll',
            'reference_id' => $payrollId,
            'amount' => 1000000,
        ]);
    }

    public function test_cannot_pay_already_paid_payroll(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $payrollId = DB::table('hr_payrolls')->insertGetId([
            'teacher_id' => $teacherId, 'business_id' => $this->businessId, 'month' => 7, 'year' => 2026,
            'total_hours' => 1, 'base_salary' => 100000, 'bonus' => 0, 'penalty' => 0, 'total_salary' => 100000,
            'status' => 'paid', 'paid_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAsAdmin($this->businessId);

        $this->postJson("/v1/hr/payroll/pay/{$payrollId}")
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Bảng lương này đã được trả.');
    }

    public function test_summary_counts_teachers_balance_and_pending(): void
    {
        [, $teacherId] = $this->actingAsTeacher(100000);
        $classId = $this->makeClassId();
        $s1 = $this->makeSessionId($classId, $teacherId, '2026-07-05', '19:00:00', '20:00:00'); // 1h
        $this->makeAttendance($s1);

        $this->travelTo(Carbon::parse('2026-07-15'));

        $this->actingAsAdmin($this->businessId);

        // Auto-generates July payroll for the teacher (status draft => "pending" this period).
        $this->getJson('/v1/hr/payroll/list?teacher_id='.$teacherId);

        $this->getJson('/v1/hr/payroll/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.teachers', 1)
            ->assertJsonPath('data.pending', 1);
    }

    public function test_detail_includes_teacher_wallet_balance(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $payrollId = DB::table('hr_payrolls')->insertGetId([
            'teacher_id' => $teacherId, 'business_id' => $this->businessId, 'month' => 7, 'year' => 2026,
            'total_hours' => 10, 'base_salary' => 1000000, 'bonus' => 0, 'penalty' => 0, 'total_salary' => 1000000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAsAdmin($this->businessId);
        $this->postJson("/v1/hr/payroll/pay/{$payrollId}")->assertStatus(200);

        $wallet = DB::table('fin_wallets')->where('owner_type', 'teacher')->where('owner_id', $teacherId)->first();

        $this->getJson("/v1/hr/payroll/detail/{$payrollId}")
            ->assertStatus(200)
            ->assertJsonPath('data.wallet_id', $wallet->id)
            ->assertJsonPath('data.balance', fn ($value) => (float) $value === 1000000.0);
    }

    public function test_non_admin_cannot_pay_own_payroll_even_if_granted_permission(): void
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, ['payroll.view', 'payroll.pay']);
        $user = $this->makeUser(false, $roleId, $this->businessId);
        $teacherId = DB::table('hr_teachers')->insertGetId([
            'user_id' => $user->id, 'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()), 'full_name' => 'Self Payer', 'hourly_rate' => 100000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $payrollId = DB::table('hr_payrolls')->insertGetId([
            'teacher_id' => $teacherId, 'business_id' => $this->businessId, 'month' => 7, 'year' => 2026,
            'total_hours' => 1, 'base_salary' => 100000, 'bonus' => 0, 'penalty' => 0, 'total_salary' => 100000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAsApi($user);

        $this->postJson("/v1/hr/payroll/pay/{$payrollId}")->assertJsonPath('code', 403);

        $this->assertDatabaseHas('hr_payrolls', ['id' => $payrollId, 'status' => 'draft']);
    }
}
