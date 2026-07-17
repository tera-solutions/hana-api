<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/sys/setting/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/sys/setting/list')->assertJsonPath('code', 403);
    }

    public function test_upsert_creates_new_setting(): void
    {
        $this->actingAsManager(['setting.update', 'setting.list']);

        $this->postJson('/v1/sys/setting/upsert', [
            'key' => 'notification.new_message',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'notification',
            'label' => 'Thông báo tin nhắn',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.key', 'notification.new_message')
            ->assertJsonPath('data.value', 'true');

        $this->getJson('/v1/sys/setting/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_upsert_updates_existing_setting_by_key(): void
    {
        $this->actingAsManager(['setting.update', 'setting.list']);

        $this->postJson('/v1/sys/setting/upsert', [
            'key' => 'general.timezone',
            'value' => 'Asia/Ho_Chi_Minh',
        ])->assertStatus(200);

        $this->postJson('/v1/sys/setting/upsert', [
            'key' => 'general.timezone',
            'value' => 'UTC',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.value', 'UTC');

        $this->getJson('/v1/sys/setting/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_upsert_requires_key(): void
    {
        $this->actingAsManager(['setting.update']);

        $this->postJson('/v1/sys/setting/upsert', ['value' => 'true'])
            ->assertStatus(422);
    }

    public function test_settings_are_scoped_to_business(): void
    {
        $this->actingAsManager(['setting.update', 'setting.list']);
        $this->postJson('/v1/sys/setting/upsert', ['key' => 'general.language', 'value' => 'vi'])
            ->assertStatus(200);

        $otherBusinessId = $this->makeBusinessId();
        $otherRoleId = $this->makeRoleId($otherBusinessId);
        $this->grantPermissions($otherRoleId, ['setting.list']);
        $this->actingAsApi($this->makeUser(false, $otherRoleId, $otherBusinessId));

        $this->getJson('/v1/sys/setting/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }

    public function test_delete_removes_setting(): void
    {
        $this->actingAsManager(['setting.update', 'setting.list']);

        $id = DB::table('sys_settings')->insertGetId([
            'business_id' => $this->primaryBusinessId,
            'key' => 'general.language',
            'value' => 'vi',
            'type' => 'string',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson("/v1/sys/setting/delete/{$id}")->assertStatus(200);

        $this->getJson('/v1/sys/setting/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }
}
