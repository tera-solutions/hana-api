<?php

namespace Tests\Feature;

use Database\Seeders\MaterialPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MaterialPermissionSeeder::class);
        $this->businessId = $this->makeBusinessId();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'material_name' => 'Workbook Starter',
            'material_type' => 'pdf',
            'access_type' => 'student',
            'description' => 'Tài liệu Starter.',
        ], $overrides);
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
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

    private function createMaterial(array $overrides = []): int
    {
        return $this->postJson('/v1/edu/material/create', $this->payload($overrides))->json('data.id');
    }

    private function upload(int $id, array $data = []): TestResponse
    {
        return $this->postJson("/v1/edu/material/upload/{$id}", $data);
    }

    private function publish(int $id): TestResponse
    {
        return $this->postJson("/v1/edu/material/publish/{$id}");
    }

    private function attach(int $id, int $entityId, string $entityType = 'course'): TestResponse
    {
        return $this->postJson("/v1/edu/material/attach/{$id}", ['entity_type' => $entityType, 'entity_id' => $entityId]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/material/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/material/list')->assertJsonPath('code', 403);
    }

    public function test_create_starts_as_draft_and_lists(): void
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/v1/edu/material/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.current_version', 0);

        // material_code is system-generated (MAT000001, …), not client-supplied.
        $this->assertMatchesRegularExpression('/^MAT\d{6}$/', $res->json('data.material_code'));

        $this->getJson('/v1/edu/material/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_create_validation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/material/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['material_name', 'material_type', 'access_type']);
    }

    public function test_versioning_and_rollback(): void
    {
        $this->actingAsAdmin();

        $id = $this->createMaterial(['file_id' => 10, 'file_name' => 'v1.pdf']);

        // First version came with creation.
        $this->assertDatabaseHas('edu_materials', ['id' => $id, 'current_version' => 1]);

        $this->upload($id, ['file_name' => 'v2.pdf', 'change_log' => 'Bổ sung'])
            ->assertStatus(200)
            ->assertJsonPath('data.version', 2);

        $this->assertDatabaseHas('edu_materials', ['id' => $id, 'current_version' => 2]);

        $this->postJson("/v1/edu/material/rollback/{$id}", ['version' => 1])
            ->assertStatus(200)
            ->assertJsonPath('data.current_version', 1);

        // Rolling back to a non-existent version is rejected.
        $this->postJson("/v1/edu/material/rollback/{$id}", ['version' => 99])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_publish_requires_a_version(): void
    {
        $this->actingAsAdmin();

        $id = $this->createMaterial();

        $this->publish($id)
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->upload($id, ['file_name' => 'v1.pdf'])->assertStatus(200);

        $this->publish($id)
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_attach_detach_and_list_mappings(): void
    {
        $this->actingAsAdmin();

        $id = $this->createMaterial();
        $courseId = $this->makeCourseId();

        $mappingId = $this->attach($id, $courseId)
            ->assertStatus(200)
            ->assertJsonPath('data.entity_type', 'course')
            ->json('data.id');

        // Idempotent — re-attaching the same target returns the same mapping.
        $this->attach($id, $courseId)
            ->assertStatus(200)
            ->assertJsonPath('data.id', $mappingId);

        $this->getJson("/v1/edu/material/mappings/{$id}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Attaching to a non-existent target is rejected.
        $this->attach($id, 99999)
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->deleteJson("/v1/edu/material/mapping/delete/{$mappingId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('edu_material_mappings', ['id' => $mappingId]);
    }

    public function test_delete(): void
    {
        $this->actingAsAdmin();

        $id = $this->createMaterial();

        $this->deleteJson("/v1/edu/material/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('edu_materials', ['id' => $id]);

        $this->getJson("/v1/edu/material/detail/{$id}")
            ->assertStatus(404);
    }

    public function test_category_crud(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/material-category/create', ['category_name' => 'Workbook', 'category_code' => 'WB'])
            ->assertStatus(200)
            ->assertJsonPath('data.category_code', 'WB')
            ->json('data.id');

        $this->putJson("/v1/edu/material-category/update/{$id}", ['category_name' => 'Workbook Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.category_name', 'Workbook Updated');

        $this->getJson('/v1/edu/material-category/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);

        $this->deleteJson("/v1/edu/material-category/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
