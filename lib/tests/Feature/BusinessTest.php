<?php

namespace Tests\Feature;

use Database\Seeders\BusinessPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Make the business.* permissions available for the permission guard.
        $this->seed(BusinessPermissionSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'business_code' => 'HCM001',
            'name' => 'Hana English HCM',
            'short_name' => 'Hana HCM',
            'prefix' => 'HCM',
            'phone' => '0901234567',
            'email' => 'hcm@hana.edu.vn',
            'address' => '123 Le Loi',
            'province' => 'Ho Chi Minh',
            'district' => 'District 1',
            'status' => 'active',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/sys/business/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/sys/business/list')->assertJsonPath('code', 403);
    }

    public function test_manager_with_permission_can_access(): void
    {
        $this->actingAsManager(['business.list']);

        $this->getJson('/v1/sys/business/list')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_manager_with_list_permission_cannot_create(): void
    {
        $this->actingAsManager(['business.list']);

        $this->postJson('/v1/sys/business/create', $this->payload())
            ->assertJsonPath('code', 403);
    }

    public function test_can_create_business(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/sys/business/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.business_code', 'HCM001');

        $this->assertDatabaseHas('sys_business', ['business_code' => 'HCM001']);
    }

    public function test_create_rejects_duplicate_business_code(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/sys/business/create', $this->payload())->assertStatus(200);

        $response = $this->postJson('/v1/sys/business/create', $this->payload([
            'email' => 'other@hana.edu.vn',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('business_code');
    }

    public function test_create_rejects_lowercase_prefix(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/sys/business/create', $this->payload(['prefix' => 'hcm']));

        $response->assertStatus(422)->assertJsonValidationErrors('prefix');
    }

    public function test_can_list_and_search_business(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/sys/business/create', $this->payload())->assertStatus(200);
        $this->postJson('/v1/sys/business/create', $this->payload([
            'business_code' => 'HN001',
            'prefix' => 'HN',
            'name' => 'Hana Ha Noi',
            'email' => 'hn@hana.edu.vn',
        ]))->assertStatus(200);

        // Scope to the two created records ("Root Business" fixtures are excluded).
        $this->getJson('/v1/sys/business/list?search=hana')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/sys/business/list?search=HN001')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.business_code', 'HN001');
    }

    public function test_detail_returns_statistics(): void
    {
        $this->actingAsAdmin();

        $create = $this->postJson('/v1/sys/business/create', $this->payload())->json('data');
        $id = $create['id'];

        $this->getJson("/v1/sys/business/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.business.id', $id)
            ->assertJsonStructure([
                'data' => [
                    'statistics' => [
                        'total_students',
                        'total_parents',
                        'total_teachers',
                        'total_courses',
                        'total_classes',
                    ],
                ],
            ]);
    }

    public function test_update_cannot_change_business_code(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/sys/business/create', $this->payload())->json('data.id');

        $this->putJson("/v1/sys/business/update/{$id}", [
            'name' => 'Renamed Center',
            'business_code' => 'CHANGED',
        ])->assertStatus(200)->assertJsonPath('data.name', 'Renamed Center');

        $this->assertDatabaseHas('sys_business', [
            'id' => $id,
            'business_code' => 'HCM001',
            'name' => 'Renamed Center',
        ]);
    }

    public function test_delete_blocked_when_linked_data_exists(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/sys/business/create', $this->payload())->json('data.id');

        DB::table('edu_students')->insert([
            'business_id' => $id,
            'code' => 'STU001',
            'name' => 'Linked Student',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson("/v1/sys/business/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('sys_business', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_delete_soft_deletes_when_no_linked_data(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/sys/business/create', $this->payload())->json('data.id');

        $this->deleteJson("/v1/sys/business/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('sys_business', ['id' => $id]);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/sys/business/create', $this->payload())->json('data.id');
        $this->assertDatabaseHas('sys_business', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->putJson("/v1/sys/business/update/{$id}", ['name' => 'Renamed'])->assertStatus(200);
        $this->assertDatabaseHas('sys_business', ['id' => $id, 'updated_by' => $admin->id]);

        $this->deleteJson("/v1/sys/business/delete/{$id}")->assertStatus(200);
        $this->assertDatabaseHas('sys_business', ['id' => $id, 'deleted_by' => $admin->id]);
    }
}
