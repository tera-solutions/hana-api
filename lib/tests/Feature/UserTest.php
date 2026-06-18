<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $roleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserPermissionSeeder::class);
        $this->businessId = $this->makeBusinessId();
        $this->roleId = $this->makeRoleId($this->businessId);
    }

    /** Local override: the User tests share one business/role and add their own fields. */
    private function makeManagedUser(bool $isAdmin, array $overrides = []): User
    {
        return User::create(array_merge([
            'full_name' => 'Tester',
            'avatar' => '',
            'username' => 'user_'.uniqid(),
            'email' => 'user_'.uniqid().'@hana.edu.vn',
            'status' => 'active',
            'code' => 'U_'.strtoupper(uniqid()),
            'is_active' => true,
            'is_admin' => $isAdmin,
            'password' => bcrypt('secret123'),
            'business_id' => $this->businessId,
            'role_id' => $this->roleId,
        ], $overrides));
    }

    private function actingAsAdmin(): User
    {
        // A second admin so delete tests aren't blocked by the "last admin" rule.
        $this->makeManagedUser(true);

        return $this->actingAsApi($this->makeManagedUser(true));
    }

    private function actingAsManager(array $permissionCodes = []): User
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, $permissionCodes);

        return $this->actingAsApi($this->makeManagedUser(false, ['role_id' => $roleId]));
    }

    private function payload(array $overrides = []): array
    {
        $uniq = uniqid();

        return array_merge([
            'full_name' => 'Nguyen Van A',
            'email' => "a_{$uniq}@hana.edu.vn",
            'phone' => '09'.substr((string) microtime(true), -8),
            'username' => "user_{$uniq}",
            'password' => 'Abc@1234',
            'password_confirmation' => 'Abc@1234',
            'business_id' => $this->businessId,
            'role_id' => $this->roleId,
            'status' => 'active',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/sys/user/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);
        $this->getJson('/v1/sys/user/list')->assertJsonPath('code', 403);
    }

    public function test_manager_with_permission_can_access(): void
    {
        $this->actingAsManager(['user.list']);
        $this->getJson('/v1/sys/user/list')->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_can_create_user_and_generates_user_id_and_hashes_password(): void
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/v1/sys/user/create', $this->payload(['username' => 'teacher01']))
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'teacher01');

        $id = $res->json('data.id');
        $this->assertSame('USR'.str_pad((string) $id, 6, '0', STR_PAD_LEFT), $res->json('data.user_id'));

        $stored = DB::table('users')->where('id', $id)->value('password');
        $this->assertNotSame('Abc@1234', $stored);
        $this->assertTrue(Hash::check('Abc@1234', $stored));
    }

    public function test_user_management_emits_activity_log(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/sys/user/create', $this->payload(['username' => 'teacher02']))
            ->json('data.id');

        $this->assertDatabaseHas('sys_activity_logs', [
            'module' => 'system',
            'entity' => 'User',
            'entity_id' => $id,
            'action' => 'created',
        ]);

        // The password must be masked in the audit payload, never stored in clear/hash.
        $newData = DB::table('sys_activity_logs')
            ->where('entity', 'User')->where('entity_id', $id)->where('action', 'created')
            ->value('new_data');
        $this->assertStringContainsString('"password":"***"', (string) $newData);
    }

    public function test_create_rejects_duplicate_username_email_phone(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/v1/sys/user/create', $this->payload([
            'username' => 'dup', 'email' => 'dup@hana.edu.vn', 'phone' => '0901112223',
        ]))->assertStatus(200);

        $this->postJson('/v1/sys/user/create', $this->payload([
            'username' => 'dup', 'email' => 'dup@hana.edu.vn', 'phone' => '0901112223',
        ]))->assertStatus(422)->assertJsonValidationErrors(['username', 'email', 'phone']);
    }

    public function test_create_rejects_weak_password_and_mismatch(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/sys/user/create', $this->payload(['password' => 'abc', 'password_confirmation' => 'abc']))
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson('/v1/sys/user/create', $this->payload(['password_confirmation' => 'Different1']))
            ->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_can_list_and_search_users(): void
    {
        $this->actingAsAdmin();
        $this->postJson('/v1/sys/user/create', $this->payload(['username' => 'findme', 'full_name' => 'Searchable Person']))->assertStatus(200);

        $this->getJson('/v1/sys/user/list?search=Searchable')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.username', 'findme');
    }

    public function test_update_cannot_change_username(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/user/create', $this->payload(['username' => 'keepme']))->json('data.id');

        $this->putJson("/v1/sys/user/update/{$id}", ['full_name' => 'New Name', 'username' => 'changed'])
            ->assertStatus(200)->assertJsonPath('data.full_name', 'New Name');

        $this->assertDatabaseHas('users', ['id' => $id, 'username' => 'keepme', 'full_name' => 'New Name']);
    }

    public function test_activate_deactivate_unlock(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/user/create', $this->payload())->json('data.id');

        $this->postJson("/v1/sys/user/deactivate/{$id}")->assertStatus(200)->assertJsonPath('data.is_active', false);
        $this->assertDatabaseHas('users', ['id' => $id, 'status' => 'inactive', 'is_active' => false]);

        $this->postJson("/v1/sys/user/activate/{$id}")->assertStatus(200)->assertJsonPath('data.is_active', true);
        $this->assertDatabaseHas('users', ['id' => $id, 'status' => 'active', 'is_active' => true]);

        DB::table('users')->where('id', $id)->update(['status' => 'locked', 'is_active' => false]);
        $this->postJson("/v1/sys/user/unlock/{$id}")->assertStatus(200)->assertJsonPath('data.status', 'active');
    }

    public function test_reset_password_returns_new_working_password(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/user/create', $this->payload())->json('data.id');

        $new = $this->postJson("/v1/sys/user/reset-password/{$id}")->assertStatus(200)->json('data.password');

        $this->assertNotEmpty($new);
        $this->assertTrue(Hash::check($new, DB::table('users')->where('id', $id)->value('password')));
    }

    public function test_delete_soft_deletes_and_deactivates(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/user/create', $this->payload())->json('data.id');

        $this->deleteJson("/v1/sys/user/delete/{$id}")->assertStatus(200)->assertJsonPath('success', true);

        $this->assertSoftDeleted('users', ['id' => $id]);
        $this->assertDatabaseHas('users', ['id' => $id, 'status' => 'inactive', 'is_active' => false]);
    }

    public function test_delete_blocked_for_last_super_admin(): void
    {
        // Only one admin in the system -> deleting it is blocked.
        $admin = $this->actingAsApi($this->makeManagedUser(true));

        $this->deleteJson("/v1/sys/user/delete/{$admin->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_delete_blocked_when_managing_a_branch(): void
    {
        $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/user/create', $this->payload())->json('data.id');

        DB::table('sys_branches')->insert([
            'business_id' => $this->businessId,
            'code' => 'BR1',
            'name' => 'Branch 1',
            'address' => 'x',
            'manager_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->deleteJson("/v1/sys/user/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();
        $id = $this->postJson('/v1/sys/user/create', $this->payload())->json('data.id');

        $this->assertDatabaseHas('users', ['id' => $id, 'created_by' => $admin->id, 'updated_by' => $admin->id]);
    }
}
