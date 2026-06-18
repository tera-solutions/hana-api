<?php

namespace Tests\Feature;

use Database\Seeders\LevelPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LevelTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LevelPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = $this->makeCourseId();
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Kids English',
            'code' => 'C_'.strtoupper(uniqid()),
            'duration_minutes' => 60,
            'price_per_lesson' => 100000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'level_code' => 'KIDS-STARTER',
            'level_name' => 'Starter',
            'course_id' => $this->courseId,
            'level_order' => 1,
            'cefr_level' => 'Pre-A1',
            'status' => 'active',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/level/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/level/list')->assertJsonPath('code', 403);
    }

    public function test_create_and_list(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/level/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.level_code', 'KIDS-STARTER')
            ->assertJsonPath('data.status', 'active');

        $this->getJson('/v1/edu/level/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_create_validation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/level/create', [])->assertStatus(422);

        $this->postJson('/v1/edu/level/create', $this->payload());
        $this->postJson('/v1/edu/level/create', $this->payload())->assertStatus(422); // duplicate code
    }

    public function test_update(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/level/create', $this->payload())->json('data.id');

        $this->putJson("/v1/edu/level/update/{$id}", ['level_name' => 'Starter Plus'])
            ->assertStatus(200)
            ->assertJsonPath('data.level_name', 'Starter Plus');
    }

    public function test_create_and_update_emit_activity_log(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/level/create', $this->payload())->json('data.id');

        $this->assertDatabaseHas('sys_activity_logs', [
            'module' => 'education',
            'entity' => 'Level',
            'entity_id' => $id,
            'action' => 'created',
        ]);

        $this->putJson("/v1/edu/level/update/{$id}", ['level_name' => 'Starter Plus']);

        $log = DB::table('sys_activity_logs')
            ->where('entity', 'Level')->where('entity_id', $id)->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('level_name', (string) $log->changed_fields);
    }

    public function test_suspend_and_restore(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/level/create', $this->payload())->json('data.id');

        $this->postJson("/v1/edu/level/suspend/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        // Suspending again is rejected as a domain error.
        $this->postJson("/v1/edu/level/suspend/{$id}")
            ->assertJsonPath('success', false);

        $this->postJson("/v1/edu/level/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }
}
