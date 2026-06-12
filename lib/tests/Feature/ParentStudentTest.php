<?php

namespace Tests\Feature;

use Database\Seeders\ParentStudentPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class ParentStudentTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $parentId;

    private int $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ParentStudentPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId($this->businessId);
        $this->parentId = $this->makeParentId();
        $this->studentId = $this->makeStudentId();
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

    private function makeParentId(?string $code = null, string $status = 'active'): int
    {
        return DB::table('crm_parents')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => $code ?? 'PAR_'.strtoupper(uniqid()),
            'name' => 'Parent '.uniqid(),
            'phone' => '0922222222',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeStudentId(string $status = 'active'): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'STD_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'parent_id' => $this->parentId,
            'student_id' => $this->studentId,
            'relation' => 'father',
            'is_primary_contact' => true,
            'is_billing_contact' => true,
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/crm/parent-student/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/crm/parent-student/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_relationship(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/crm/parent-student/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.relation', 'father')
            ->assertJsonPath('data.is_primary_contact', true);

        $this->assertDatabaseHas('crm_parent_student', [
            'parent_id' => $this->parentId,
            'student_id' => $this->studentId,
            'relation' => 'father',
            'is_primary_contact' => true,
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/parent-student/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id', 'student_id', 'relation']);
    }

    public function test_create_rejects_inactive_student(): void
    {
        $this->actingAsAdmin();

        $stoppedStudent = $this->makeStudentId('stopped');

        $this->postJson('/v1/crm/parent-student/create', $this->payload(['student_id' => $stoppedStudent]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('student_id');
    }

    public function test_create_rejects_duplicate_triple(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/parent-student/create', $this->payload())->assertStatus(200);

        $this->postJson('/v1/crm/parent-student/create', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('relation');
    }

    public function test_same_pair_allows_different_relation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/parent-student/create', $this->payload(['relation' => 'father']))
            ->assertStatus(200);
        $this->postJson('/v1/crm/parent-student/create', $this->payload(['relation' => 'guardian', 'is_primary_contact' => false]))
            ->assertStatus(200);

        $this->assertDatabaseCount('crm_parent_student', 2);
    }

    public function test_can_list_and_search(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/parent-student/create', $this->payload())->assertStatus(200);

        $this->getJson('/v1/crm/parent-student/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);

        $this->getJson("/v1/crm/parent-student/list?student_id={$this->studentId}")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_can_get_detail(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/parent-student/create', $this->payload())->json('data.id');

        $this->getJson("/v1/crm/parent-student/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.parent.id', $this->parentId)
            ->assertJsonPath('data.student.id', $this->studentId);
    }

    public function test_update_changes_flags_but_not_parent_or_student(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/parent-student/create', $this->payload())->json('data.id');

        $otherParent = $this->makeParentId();

        $this->putJson("/v1/crm/parent-student/update/{$id}", [
            'relation' => 'guardian',
            'is_billing_contact' => false,
            'parent_id' => $otherParent,
        ])->assertStatus(200)->assertJsonPath('data.relation', 'guardian');

        $this->assertDatabaseHas('crm_parent_student', [
            'id' => $id,
            'parent_id' => $this->parentId, // unchanged
            'relation' => 'guardian',
            'is_billing_contact' => false,
        ]);
    }

    public function test_delete_blocked_when_last_primary_contact(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/parent-student/create', $this->payload(['is_primary_contact' => true]))
            ->json('data.id');

        $this->deleteJson("/v1/crm/parent-student/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('crm_parent_student', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_delete_allowed_when_another_primary_contact_exists(): void
    {
        $this->actingAsAdmin();

        $first = $this->postJson('/v1/crm/parent-student/create', $this->payload(['is_primary_contact' => true]))
            ->json('data.id');

        // A second primary contact for the same student (different parent).
        $this->postJson('/v1/crm/parent-student/create', $this->payload([
            'parent_id' => $this->makeParentId(),
            'relation' => 'mother',
            'is_primary_contact' => true,
        ]))->assertStatus(200);

        $this->deleteJson("/v1/crm/parent-student/delete/{$first}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('crm_parent_student', ['id' => $first]);
    }

    public function test_delete_allowed_for_non_primary_contact(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/parent-student/create', $this->payload(['is_primary_contact' => false]))
            ->json('data.id');

        $this->deleteJson("/v1/crm/parent-student/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('crm_parent_student', ['id' => $id]);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/parent-student/create', $this->payload())->json('data.id');

        $this->assertDatabaseHas('crm_parent_student', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
