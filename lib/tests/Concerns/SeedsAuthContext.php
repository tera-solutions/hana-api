<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

/**
 * Helpers for building an authenticated business/role/user context in feature
 * tests, and acting as that user on the `api` (Passport) guard.
 */
trait SeedsAuthContext
{
    /**
     * The first business created in the test, reused as the default tenant for
     * actingAsAdmin()/actingAsManager() so the acting user and the data the
     * test seeds live in the same business (required by tenant scoping).
     */
    protected ?int $primaryBusinessId = null;

    protected function makeBusinessId(string $status = 'active'): int
    {
        $id = DB::table('sys_business')->insertGetId([
            'name' => 'Biz '.uniqid(),
            'email' => 'biz-'.uniqid().'@example.test',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->primaryBusinessId ??= $id;

        return $id;
    }

    protected function makeRoleId(int $businessId): int
    {
        return DB::table('sys_roles')->insertGetId([
            'business_id' => $businessId,
            'title' => 'Role '.uniqid(),
            'code' => 'ROLE_'.strtoupper(uniqid()),
            'type' => 'system',
            'guard_name' => 'api',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeUser(bool $isAdmin, int $roleId, int $businessId, array $overrides = []): User
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
            'business_id' => $businessId,
            'role_id' => $roleId,
        ], $overrides));
    }

    /** Grant the given permission codes to a role via role_has_permissions. */
    protected function grantPermissions(int $roleId, array $permissionCodes): void
    {
        foreach ($permissionCodes as $code) {
            $permissionId = DB::table('sys_permissions')->where('code', $code)->value('id');

            if (! $permissionId) {
                continue;
            }

            DB::table('role_has_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'code' => $roleId.'.'.$code,
            ]);
        }
    }

    protected function actingAsApi(User $user): User
    {
        Passport::actingAs($user, [], 'api');

        return $user;
    }

    /** Authenticate as a Super Admin / Admin (full access via is_admin bypass). */
    protected function actingAsAdmin(?int $businessId = null): User
    {
        $businessId ??= $this->primaryBusinessId ?? $this->makeBusinessId();

        return $this->actingAsApi($this->makeUser(true, $this->makeRoleId($businessId), $businessId));
    }

    /** Authenticate as a Manager whose role is granted the given permission codes. */
    protected function actingAsManager(array $permissionCodes = [], ?int $businessId = null): User
    {
        $businessId ??= $this->primaryBusinessId ?? $this->makeBusinessId();
        $roleId = $this->makeRoleId($businessId);
        $this->grantPermissions($roleId, $permissionCodes);

        return $this->actingAsApi($this->makeUser(false, $roleId, $businessId));
    }
}
