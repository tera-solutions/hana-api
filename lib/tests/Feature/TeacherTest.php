<?php

namespace Tests\Feature;

use Database\Seeders\TeacherPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        // Make the teacher.* permissions available for the permission guard.
        $this->seed(TeacherPermissionSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'T0001',
            'name' => 'Jane Doe',
            'type' => 'teacher',
            'status' => 'active',
            'salary_per_hour' => 150000,
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

    public function test_manager_with_permission_can_access(): void
    {
        $this->actingAsManager(['teacher.list']);

        $this->getJson('/v1/hr/teacher/list')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_manager_with_list_permission_cannot_create(): void
    {
        $this->actingAsManager(['teacher.list']);

        $this->postJson('/v1/hr/teacher/create', $this->payload())
            ->assertJsonPath('code', 403);
    }

    public function test_can_create_teacher(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/hr/teacher/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'T0001');

        $this->assertDatabaseHas('hr_teachers', ['code' => 'T0001', 'name' => 'Jane Doe']);
    }

    public function test_create_requires_code_and_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/hr/teacher/create', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['code', 'name']);
    }

    public function test_create_rejects_duplicate_code(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/hr/teacher/create', $this->payload())->assertStatus(200);

        $response = $this->postJson('/v1/hr/teacher/create', $this->payload(['name' => 'Other']));

        $response->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_can_list_teachers(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/hr/teacher/create', $this->payload())->assertStatus(200);
        $this->postJson('/v1/hr/teacher/create', $this->payload(['code' => 'T0002', 'name' => 'John Roe']))
            ->assertStatus(200);

        $this->getJson('/v1/hr/teacher/list')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_list_can_search_teachers(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/hr/teacher/create', $this->payload())->assertStatus(200);
        $this->postJson('/v1/hr/teacher/create', $this->payload(['code' => 'T0002', 'name' => 'John Roe']))
            ->assertStatus(200);

        $this->getJson('/v1/hr/teacher/list?search=T0002')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.code', 'T0002');
    }

    public function test_can_get_teacher_detail(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        $this->getJson("/v1/hr/teacher/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.teacher.id', $id)
            ->assertJsonPath('data.teacher.code', 'T0001')
            ->assertJsonStructure([
                'data' => [
                    'statistics' => [
                        'total_classes',
                        'total_sessions',
                        'total_contracts',
                        'total_payrolls',
                        'total_reviews',
                    ],
                ],
            ]);
    }

    public function test_can_update_teacher(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        $this->putJson("/v1/hr/teacher/update/{$id}", ['name' => 'Jane Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Jane Updated');

        $this->assertDatabaseHas('hr_teachers', ['id' => $id, 'name' => 'Jane Updated']);
    }

    public function test_update_rejects_duplicate_code(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/hr/teacher/create', $this->payload())->assertStatus(200);
        $id = $this->postJson('/v1/hr/teacher/create', $this->payload(['code' => 'T0002', 'name' => 'John Roe']))
            ->json('data.id');

        $this->putJson("/v1/hr/teacher/update/{$id}", ['code' => 'T0001'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_delete_blocked_when_linked_data_exists(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        DB::table('hr_contracts')->insert([
            'teacher_id' => $id,
            'type' => 'fulltime',
            'start_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson("/v1/hr/teacher/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('hr_teachers', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_delete_soft_deletes_when_no_linked_data(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');

        $this->deleteJson("/v1/hr/teacher/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('hr_teachers', ['id' => $id]);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/hr/teacher/create', $this->payload())->json('data.id');
        $this->assertDatabaseHas('hr_teachers', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->putJson("/v1/hr/teacher/update/{$id}", ['name' => 'Renamed'])->assertStatus(200);
        $this->assertDatabaseHas('hr_teachers', ['id' => $id, 'updated_by' => $admin->id]);

        $this->deleteJson("/v1/hr/teacher/delete/{$id}")->assertStatus(200);
        $this->assertDatabaseHas('hr_teachers', ['id' => $id, 'deleted_by' => $admin->id]);
    }
}
