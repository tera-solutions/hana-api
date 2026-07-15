<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class ParentTest extends TestCase
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

    private function makeStudentId(string $code = 'STD000001'): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => $code,
            'name' => 'Linked Student',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Robert Smith',
            'gender' => 'male',
            'phone' => '0922222222',
            'email' => 'robert@example.com',
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'occupation' => 'Engineer',
            'address' => '123 Le Loi',
            'province' => 'Ho Chi Minh',
            'district' => 'District 7',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/crm/parent/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/crm/parent/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_parent_and_generates_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/crm/parent/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'PAR000001')
            ->assertJsonPath('data.status', 'active');

        $id = $response->json('data.id');

        $this->assertDatabaseHas('crm_parents', ['id' => $id, 'code' => 'PAR000001', 'status' => 'active']);
        $this->assertDatabaseHas('crm_parent_histories', ['parent_id' => $id, 'action' => 'created']);
    }

    public function test_code_increments_per_parent(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/parent/create', $this->payload())
            ->assertJsonPath('data.code', 'PAR000001');
        $this->postJson('/v1/crm/parent/create', $this->payload(['name' => 'B']))
            ->assertJsonPath('data.code', 'PAR000002');
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/parent/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'business_id', 'branch_id']);
    }

    public function test_create_links_student(): void
    {
        $this->actingAsAdmin();

        $studentId = $this->makeStudentId();

        $id = $this->postJson('/v1/crm/parent/create', $this->payload([
            'students' => [
                ['student_id' => $studentId, 'relation' => 'father'],
            ],
        ]))->json('data.id');

        $this->assertDatabaseHas('crm_parent_student', [
            'parent_id' => $id,
            'student_id' => $studentId,
            'relation' => 'father',
        ]);
    }

    public function test_can_list_and_search_parents(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/parent/create', $this->payload())->assertStatus(200);
        $this->postJson('/v1/crm/parent/create', $this->payload(['name' => 'Unique Guardian']))->assertStatus(200);

        $this->getJson('/v1/crm/parent/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/crm/parent/list?search=Unique')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.name', 'Unique Guardian');
    }

    public function test_detail_returns_financial_statistics(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/parent/create', $this->payload())->json('data.id');

        $this->getJson("/v1/crm/parent/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.parent.id', $id)
            ->assertJsonStructure([
                'data' => ['statistics' => ['total_students', 'total_invoices', 'total_payments', 'total_debts']],
            ]);
    }

    public function test_update_ignores_immutable_fields(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/parent/create', $this->payload())->json('data.id');

        $this->putJson("/v1/crm/parent/update/{$id}", [
            'name' => 'Renamed',
            'code' => 'HACKED',
            'status' => 'inactive',
        ])->assertStatus(200)->assertJsonPath('data.name', 'Renamed');

        $this->assertDatabaseHas('crm_parents', [
            'id' => $id,
            'name' => 'Renamed',
            'code' => 'PAR000001',
            'status' => 'active',
        ]);
    }

    public function test_suspend_keeps_student_links_and_restore_lifecycle(): void
    {
        $this->actingAsAdmin();

        $studentId = $this->makeStudentId();

        $id = $this->postJson('/v1/crm/parent/create', $this->payload([
            'students' => [['student_id' => $studentId, 'relation' => 'father']],
        ]))->json('data.id');

        $this->postJson("/v1/crm/parent/suspend/{$id}", ['suspend_date' => '2026-06-12', 'reason' => 'Stopped contact'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        // Student link is preserved while suspended (parent.md §6).
        $this->assertDatabaseHas('crm_parent_student', ['parent_id' => $id, 'student_id' => $studentId]);
        $this->assertDatabaseHas('crm_parent_histories', ['parent_id' => $id, 'action' => 'suspended']);

        // Suspending again is rejected.
        $this->postJson("/v1/crm/parent/suspend/{$id}", ['suspend_date' => '2026-06-12', 'reason' => 'x'])
            ->assertJsonPath('success', false);

        $this->postJson("/v1/crm/parent/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('crm_parent_histories', ['parent_id' => $id, 'action' => 'restored']);
    }

    public function test_restore_rejected_when_not_suspended(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/parent/create', $this->payload())->json('data.id');

        $this->postJson("/v1/crm/parent/restore/{$id}")
            ->assertJsonPath('success', false);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/parent/create', $this->payload())->json('data.id');

        $this->assertDatabaseHas('crm_parents', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
