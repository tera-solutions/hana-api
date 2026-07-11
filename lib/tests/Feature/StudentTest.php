<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $levelId;

    protected function setUp(): void
    {
        parent::setUp();

        // Make the student.* permissions available for the permission guard.
        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId($this->businessId);
        $this->levelId = $this->makeLevelId();
    }

    private function makeLevelId(): int
    {
        return DB::table('edu_levels')->insertGetId([
            'level_code' => 'A1_'.strtoupper(uniqid()),
            'level_name' => 'A1',
            'level_order' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyen Van A',
            'dob' => '2010-05-12',
            'gender' => 'male',
            'email' => 'a@gmail.com',
            'phone' => '0901234567',
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'level_id' => $this->levelId,
            'enrollment_date' => '2026-06-01',
            'address' => '123 Le Loi',
            'province' => 'Ho Chi Minh',
            'district' => 'District 7',
        ], $overrides);
    }

    private function createStudent(array $overrides = []): int
    {
        return $this->postJson('/v1/edu/student/create', $this->payload($overrides))->json('data.id');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/student/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/student/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_student_and_generates_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/edu/student/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'STD000001')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.level_id', $this->levelId);

        $id = $response->json('data.id');

        $this->assertDatabaseHas('edu_students', ['id' => $id, 'code' => 'STD000001', 'status' => 'active']);
        $this->assertDatabaseHas('edu_student_profiles', ['student_id' => $id, 'province' => 'Ho Chi Minh']);
        $this->assertDatabaseHas('edu_student_histories', ['student_id' => $id, 'action' => 'created']);
    }

    public function test_code_increments_per_student(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/student/create', $this->payload())
            ->assertJsonPath('data.code', 'STD000001');
        $this->postJson('/v1/edu/student/create', $this->payload(['name' => 'B']))
            ->assertJsonPath('data.code', 'STD000002');
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/student/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'dob', 'gender', 'business_id', 'branch_id', 'enrollment_date']);
    }

    public function test_create_rejects_future_dob(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/student/create', $this->payload(['dob' => now()->addDay()->toDateString()]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('dob');
    }

    public function test_create_assigns_new_parent(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudent([
            'parents' => [
                ['name' => 'Tran Thi B', 'phone' => '0907654321', 'relation' => 'mother'],
            ],
        ]);

        $this->assertDatabaseHas('crm_parents', ['name' => 'Tran Thi B']);
        $this->assertDatabaseHas('crm_parent_student', ['student_id' => $id, 'relation' => 'mother']);
    }

    public function test_can_list_and_search_students(): void
    {
        $this->actingAsAdmin();

        $this->createStudent();
        $this->createStudent(['name' => 'Unique Person']);

        $this->getJson('/v1/edu/student/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/edu/student/list?search=Unique')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.name', 'Unique Person');
    }

    public function test_can_get_detail_with_statistics(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudent();

        $this->getJson("/v1/edu/student/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.student.id', $id)
            ->assertJsonStructure([
                'data' => ['statistics' => ['total_enrollments', 'total_invoices', 'total_exam_results']],
            ]);
    }

    public function test_update_ignores_immutable_fields(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudent();

        $this->putJson("/v1/edu/student/update/{$id}", [
            'name' => 'Renamed',
            'code' => 'HACKED',
            'status' => 'graduated',
        ])->assertStatus(200)->assertJsonPath('data.name', 'Renamed');

        $this->assertDatabaseHas('edu_students', [
            'id' => $id,
            'name' => 'Renamed',
            'code' => 'STD000001',
            'status' => 'active',
        ]);
    }

    public function test_suspend_and_restore_lifecycle(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudent();

        $this->postJson("/v1/edu/student/suspend/{$id}", ['stop_date' => '2026-06-12', 'reason' => 'Nghỉ dài hạn'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('edu_student_histories', ['student_id' => $id, 'action' => 'suspended']);

        // Suspending again is rejected.
        $this->postJson("/v1/edu/student/suspend/{$id}", ['stop_date' => '2026-06-12', 'reason' => 'x'])
            ->assertJsonPath('success', false);

        $this->postJson("/v1/edu/student/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('edu_student_histories', ['student_id' => $id, 'action' => 'restored']);
    }

    public function test_restore_rejected_when_not_suspended(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudent();

        $this->postJson("/v1/edu/student/restore/{$id}")
            ->assertJsonPath('success', false);
    }

    public function test_delete_soft_deletes_student(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudent();

        $this->deleteJson("/v1/edu/student/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('edu_students', ['id' => $id]);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->createStudent();

        $this->assertDatabaseHas('edu_students', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
