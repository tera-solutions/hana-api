<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function makeLog(array $overrides = []): int
    {
        return DB::table('sys_activity_logs')->insertGetId(array_merge([
            'module' => 'education',
            'entity' => 'Student',
            'entity_id' => '1',
            'action' => 'update',
            'user_id' => 1,
            'status' => 'success',
            'description' => 'Updated student',
            'created_at' => now(),
        ], $overrides));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/sys/activity-log/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/sys/activity-log/list')->assertJsonPath('code', 403);
    }

    public function test_list_and_filter(): void
    {
        $this->actingAsAdmin();
        // Acting-as creates a User, which now emits its own audit row; isolate the fixture.
        DB::table('sys_activity_logs')->delete();

        $this->makeLog(['module' => 'education', 'action' => 'update']);
        $this->makeLog(['module' => 'finance', 'action' => 'pay', 'entity' => 'Invoice']);

        $this->getJson('/v1/sys/activity-log/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/sys/activity-log/list?module=finance')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.action', 'pay');

        $this->getJson('/v1/sys/activity-log/list?action=update')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_detail(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeLog(['description' => 'Created invoice', 'action' => 'create']);

        $this->getJson("/v1/sys/activity-log/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.action', 'create')
            ->assertJsonPath('data.description', 'Created invoice');
    }

    public function test_statistics(): void
    {
        $this->actingAsAdmin();
        // Acting-as creates a User, which now emits its own audit row; isolate the fixture.
        DB::table('sys_activity_logs')->delete();

        $this->makeLog(['module' => 'education', 'action' => 'update']);
        $this->makeLog(['module' => 'education', 'action' => 'create']);
        $this->makeLog(['module' => 'finance', 'action' => 'login', 'status' => 'failed']);

        $this->getJson('/v1/sys/activity-log/statistics')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.by_module.education', 2)
            ->assertJsonPath('data.failed_logins', 1);
    }
}
