<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function payload(int $businessId, array $overrides = []): array
    {
        return array_merge([
            'business_id' => $businessId,
            'code' => 'Q1',
            'name' => 'Chi nhanh Quan 1',
            'short_name' => 'Q1',
            'status' => 'active',
            'phone' => '0901234567',
            'email' => 'q1-'.uniqid().'@hana.edu.vn',
            'address' => '123 Le Loi',
            'province' => 'Ho Chi Minh',
            'district' => 'District 1',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/sys/branch/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);
        $this->getJson('/v1/sys/branch/list')->assertJsonPath('code', 403);
    }

    public function test_manager_with_permission_can_access(): void
    {
        $this->actingAsManager(['branch.list']);
        $this->getJson('/v1/sys/branch/list')->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_create_branch(): void
    {
        $this->actingAsAdmin();
        $businessId = $this->makeBusinessId();

        $this->postJson('/v1/sys/branch/create', $this->payload($businessId))
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'Q1');

        $this->assertDatabaseHas('sys_branches', ['business_id' => $businessId, 'code' => 'Q1']);
    }

    public function test_create_rejects_duplicate_code_in_same_business(): void
    {
        $this->actingAsAdmin();
        $businessId = $this->makeBusinessId();

        $this->postJson('/v1/sys/branch/create', $this->payload($businessId))->assertStatus(200);

        $this->postJson('/v1/sys/branch/create', $this->payload($businessId))
            ->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_create_allows_same_code_in_different_business(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/sys/branch/create', $this->payload($this->makeBusinessId()))->assertStatus(200);
        $this->postJson('/v1/sys/branch/create', $this->payload($this->makeBusinessId()))->assertStatus(200);
    }

    public function test_create_rejects_inactive_business(): void
    {
        $this->actingAsAdmin();
        $inactiveBiz = $this->makeBusinessId('inactive');

        $this->postJson('/v1/sys/branch/create', $this->payload($inactiveBiz))
            ->assertStatus(422)->assertJsonValidationErrors('business_id');
    }

    public function test_can_list_and_search_branch(): void
    {
        $this->actingAsAdmin();
        $businessId = $this->makeBusinessId();

        $this->postJson('/v1/sys/branch/create', $this->payload($businessId, ['code' => 'Q1', 'name' => 'Quan 1']))->assertStatus(200);
        $this->postJson('/v1/sys/branch/create', $this->payload($businessId, ['code' => 'Q7', 'name' => 'Quan 7']))->assertStatus(200);

        $this->getJson('/v1/sys/branch/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/sys/branch/list?search=Q7')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.code', 'Q7');
    }

    public function test_detail_returns_statistics(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/branch/create', $this->payload($this->makeBusinessId()))->json('data.id');

        $this->getJson("/v1/sys/branch/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.branch.id', $id)
            ->assertJsonStructure([
                'data' => [
                    'statistics' => [
                        'total_students', 'total_parents', 'total_teachers',
                        'total_classes', 'total_rooms', 'total_courses',
                    ],
                ],
            ]);
    }

    public function test_update_cannot_change_code_or_business(): void
    {
        $this->actingAsAdmin();
        $businessId = $this->makeBusinessId();
        $id = $this->postJson('/v1/sys/branch/create', $this->payload($businessId))->json('data.id');

        $this->putJson("/v1/sys/branch/update/{$id}", [
            'name' => 'Renamed Branch',
            'code' => 'CHANGED',
            'business_id' => $this->makeBusinessId(),
        ])->assertStatus(200)->assertJsonPath('data.name', 'Renamed Branch');

        $this->assertDatabaseHas('sys_branches', [
            'id' => $id,
            'code' => 'Q1',
            'business_id' => $businessId,
            'name' => 'Renamed Branch',
        ]);
    }

    public function test_delete_blocked_when_linked_data_exists(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/branch/create', $this->payload($this->makeBusinessId()))->json('data.id');

        DB::table('edu_students')->insert([
            'branch_id' => $id,
            'code' => 'STU001',
            'name' => 'Linked Student',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson("/v1/sys/branch/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('sys_branches', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_delete_soft_deletes_when_no_linked_data(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/branch/create', $this->payload($this->makeBusinessId()))->json('data.id');

        $this->deleteJson("/v1/sys/branch/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('sys_branches', ['id' => $id]);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/branch/create', $this->payload($this->makeBusinessId()))->json('data.id');

        $this->assertDatabaseHas('sys_branches', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->putJson("/v1/sys/branch/update/{$id}", ['name' => 'Renamed'])->assertStatus(200);
        $this->assertDatabaseHas('sys_branches', ['id' => $id, 'updated_by' => $admin->id]);

        $this->deleteJson("/v1/sys/branch/delete/{$id}")->assertStatus(200);
        $this->assertDatabaseHas('sys_branches', ['id' => $id, 'deleted_by' => $admin->id]);
    }
}
