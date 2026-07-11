<?php

namespace Tests\Feature;

use App\Modules\HR\Teacher\Models\Teacher;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId($this->businessId);
    }

    private function makeBranchId(int $businessId): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'CN_'.strtoupper(uniqid()),
            'address' => '123 Le Loi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linkClassToTeacher(int $teacherId): void
    {
        $programId = DB::table('edu_programs')->insertGetId(['name' => 'IELTS', 'created_at' => now(), 'updated_at' => now()]);
        $levelId = DB::table('edu_levels')->insertGetId(['level_code' => 'A1_'.strtoupper(uniqid()), 'level_name' => 'A1', 'level_order' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $courseId = DB::table('edu_courses')->insertGetId([
            'program_id' => $programId, 'level_id' => $levelId, 'business_id' => $this->businessId,
            'name' => 'IELTS Foundation', 'code' => 'C_'.strtoupper(uniqid()), 'duration_minutes' => 60, 'price_per_lesson' => 1000000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $classId = DB::table('edu_classes')->insertGetId([
            'course_id' => $courseId, 'business_id' => $this->businessId, 'name' => 'Class A',
            'start_date' => now()->toDateString(), 'status' => 'running', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('edu_class_teacher')->insert([
            'class_id' => $classId, 'teacher_id' => $teacherId, 'role' => 'main',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Jane Doe',
            'code' => 'T0001',
            'gender' => 'female',
            'email' => 'jane@hana.edu.vn',
            'phone' => '0901234567',
            'branch_id' => $this->branchId,
            'joined_at' => '2026-01-10',
            'teacher_type' => 'full_time',
            'employment_type' => 'contract',
            'hourly_rate' => 150000,
            'business_id' => $this->businessId,
            'skills' => [['skill_name' => 'IELTS', 'level' => 'expert']],
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/hr/teacher/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/hr/teacher/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_teacher_with_skills(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/hr/teacher/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'T0001')
            ->assertJsonPath('data.full_name', 'Jane Doe')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.skills.0.skill_name', 'IELTS');

        $id = $response->json('data.id');
        $this->assertDatabaseHas('hr_teachers', ['id' => $id, 'code' => 'T0001', 'teacher_type' => 'full_time']);
        $this->assertDatabaseHas('hr_teacher_skills', ['teacher_id' => $id, 'skill_name' => 'IELTS']);
        $this->assertDatabaseHas('hr_teacher_histories', ['teacher_id' => $id, 'action' => 'created']);
    }

    public function test_can_set_and_update_bank_account(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload([
            'bank_account' => [
                'bank_name' => 'Vietcombank',
                'bank_account_number' => '0123456789',
                'bank_account_holder' => 'NGUYEN VAN A',
                'bank_branch' => 'Chi nhánh Hà Nội',
            ],
        ]))->assertStatus(200)
            ->assertJsonPath('data.bank_account.bank_account_number', '0123456789')
            ->json('data.id');

        $this->assertDatabaseHas('fin_bank_accounts', [
            'owner_type' => Teacher::class,
            'owner_id' => $id,
            'bank_account_number' => '0123456789',
            'bank_account_holder' => 'NGUYEN VAN A',
        ]);

        // Update upserts the same single account (no duplicate row).
        $this->putJson("/v1/hr/teacher/update/{$id}", [
            'bank_account' => ['bank_name' => 'Techcombank', 'bank_account_number' => '9999'],
        ])->assertStatus(200)
            ->assertJsonPath('data.bank_account.bank_name', 'Techcombank');

        $this->assertSame(1, DB::table('fin_bank_accounts')->where('owner_id', $id)->count());
        $this->assertDatabaseHas('fin_bank_accounts', ['owner_id' => $id, 'bank_account_number' => '9999']);
    }

    public function test_create_validates_required_and_enums(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/hr/teacher/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'code', 'email', 'phone', 'branch_id', 'joined_at', 'teacher_type', 'employment_type', 'skills']);

        $this->postJson('/v1/hr/teacher/create', $this->payload(['teacher_type' => 'invalid']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('teacher_type');
    }

    public function test_create_rejects_duplicate_code_email_phone(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/hr/teacher/create', $this->payload())->assertStatus(200);

        $this->postJson('/v1/hr/teacher/create', $this->payload(['code' => 'T0002', 'email' => 'jane@hana.edu.vn', 'phone' => '0900000000']))
            ->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('/v1/hr/teacher/create', $this->payload(['code' => 'T0002', 'email' => 'x@hana.edu.vn', 'phone' => '0901234567']))
            ->assertStatus(422)->assertJsonValidationErrors('phone');
        $this->postJson('/v1/hr/teacher/create', $this->payload(['code' => 'T0001', 'email' => 'x@hana.edu.vn', 'phone' => '0900000000']))
            ->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_list_search_and_filter(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/hr/teacher/create', $this->payload())->assertStatus(200);
        $this->postJson('/v1/hr/teacher/create', $this->payload([
            'code' => 'T0002', 'full_name' => 'John Native', 'email' => 'john@hana.edu.vn',
            'phone' => '0907654321', 'teacher_type' => 'part_time',
        ]))->assertStatus(200);

        $this->getJson('/v1/hr/teacher/list')->assertJsonPath('data.pagination.total', 2);
        $this->getJson('/v1/hr/teacher/list?search=Native')
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.code', 'T0002');
        $this->getJson('/v1/hr/teacher/list?teacher_type=part_time')
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_detail_returns_statistics(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        $this->getJson("/v1/hr/teacher/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.teacher.id', $id)
            ->assertJsonStructure(['data' => ['statistics' => ['total_classes', 'total_sessions', 'average_rating']]]);
    }

    public function test_update_replaces_skills_and_keeps_code(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        $this->putJson("/v1/hr/teacher/update/{$id}", [
            'full_name' => 'Jane Updated',
            'code' => 'HACKED',
            'skills' => [['skill_name' => 'TOEIC', 'level' => 'intermediate']],
        ])->assertStatus(200)->assertJsonPath('data.full_name', 'Jane Updated');

        $this->assertDatabaseHas('hr_teachers', ['id' => $id, 'full_name' => 'Jane Updated', 'code' => 'T0001']);
        $this->assertDatabaseHas('hr_teacher_skills', ['teacher_id' => $id, 'skill_name' => 'TOEIC']);
        $this->assertDatabaseMissing('hr_teacher_skills', ['teacher_id' => $id, 'skill_name' => 'IELTS']);
    }

    public function test_suspend_and_restore_lifecycle(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        $this->postJson("/v1/hr/teacher/suspend/{$id}", ['reason' => 'Tạm nghỉ'])
            ->assertStatus(200)->assertJsonPath('data.status', 'suspended');
        $this->assertDatabaseHas('hr_teacher_histories', ['teacher_id' => $id, 'action' => 'suspended']);

        $this->postJson("/v1/hr/teacher/suspend/{$id}", ['reason' => 'x'])->assertJsonPath('success', false);

        $this->postJson("/v1/hr/teacher/restore/{$id}")
            ->assertStatus(200)->assertJsonPath('data.status', 'active');
    }

    public function test_resign_blocked_when_holding_classes(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        $this->linkClassToTeacher($id);

        $this->postJson("/v1/hr/teacher/resign/{$id}", ['resigned_at' => '2026-06-30', 'reason' => 'Quit'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('hr_teachers', ['id' => $id, 'status' => 'active']);
    }

    public function test_resign_succeeds_when_no_classes(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        $this->postJson("/v1/hr/teacher/resign/{$id}", ['resigned_at' => '2026-06-30', 'reason' => 'Quit'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'resigned');

        $this->assertDatabaseHas('hr_teachers', ['id' => $id, 'status' => 'resigned']);
        $this->assertNotNull(DB::table('hr_teachers')->where('id', $id)->value('resigned_at'));
        $this->assertDatabaseHas('hr_teacher_histories', ['teacher_id' => $id, 'action' => 'resigned']);
    }

    public function test_certificate_crud_and_expiry_flags(): void
    {
        $this->actingAsAdmin();

        $teacherId = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        // Create one expiring soon (<= 30 days).
        $soon = now()->addDays(10)->toDateString();
        $certId = $this->postJson("/v1/hr/teacher/certificate/create/{$teacherId}", [
            'certificate_name' => 'IELTS 8.0',
            'issuer' => 'British Council',
            'expired_date' => $soon,
        ])->assertStatus(200)->assertJsonPath('data.is_expiring_soon', true)->json('data.id');

        $this->assertDatabaseHas('hr_teacher_certificates', ['id' => $certId, 'teacher_id' => $teacherId]);

        $this->getJson("/v1/hr/teacher/certificate/list/{$teacherId}")
            ->assertStatus(200)
            ->assertJsonPath('data.0.certificate_name', 'IELTS 8.0');

        $this->putJson("/v1/hr/teacher/certificate/update/{$certId}", ['certificate_name' => 'IELTS 8.5'])
            ->assertStatus(200)->assertJsonPath('data.certificate_name', 'IELTS 8.5');

        $this->deleteJson("/v1/hr/teacher/certificate/delete/{$certId}")
            ->assertStatus(200)->assertJsonPath('success', true);
        $this->assertSoftDeleted('hr_teacher_certificates', ['id' => $certId]);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        $this->assertDatabaseHas('hr_teachers', ['id' => $id, 'created_by' => $admin->id, 'updated_by' => $admin->id]);
    }
}
