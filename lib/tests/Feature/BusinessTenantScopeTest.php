<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

/**
 * Business is the tenant root and carries no BusinessScope, so /v1/sys/business/*
 * is the one place a tenant admin could otherwise read or write every other
 * tenant on the platform.
 */
class BusinessTenantScopeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function makeNamedBusinessId(string $name): int
    {
        return DB::table('sys_business')->insertGetId([
            'name' => $name,
            'email' => strtolower($name).'-'.uniqid().'@example.test',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_tenant_admin_lists_only_its_own_business(): void
    {
        $ownId = $this->makeNamedBusinessId('Own Center');
        $otherId = $this->makeNamedBusinessId('Rival Center');

        $this->actingAsAdmin($ownId);

        $response = $this->getJson('/v1/sys/business/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $ownId);

        $this->assertNotContains($otherId, array_column($response->json('data.items'), 'id'));
    }

    public function test_tenant_admin_cannot_search_across_tenants(): void
    {
        $ownId = $this->makeNamedBusinessId('Own Center');
        $this->makeNamedBusinessId('Rival Center');

        $this->actingAsAdmin($ownId);

        $this->getJson('/v1/sys/business/list?search=Rival')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }

    public function test_tenant_admin_cannot_read_another_business_detail(): void
    {
        $ownId = $this->makeNamedBusinessId('Own Center');
        $otherId = $this->makeNamedBusinessId('Rival Center');

        $this->actingAsAdmin($ownId);

        $this->getJson("/v1/sys/business/detail/{$otherId}")->assertStatus(404);
    }

    public function test_tenant_admin_reads_its_own_business_detail(): void
    {
        $ownId = $this->makeNamedBusinessId('Own Center');

        $this->actingAsAdmin($ownId);

        $this->getJson("/v1/sys/business/detail/{$ownId}")
            ->assertStatus(200)
            ->assertJsonPath('data.business.id', $ownId);
    }

    public function test_tenant_admin_cannot_update_another_business(): void
    {
        $ownId = $this->makeNamedBusinessId('Own Center');
        $otherId = $this->makeNamedBusinessId('Rival Center');

        $this->actingAsAdmin($ownId);

        $this->putJson("/v1/sys/business/update/{$otherId}", ['name' => 'Hijacked'])
            ->assertStatus(404);

        $this->assertDatabaseHas('sys_business', ['id' => $otherId, 'name' => 'Rival Center']);
    }

    public function test_tenant_admin_updates_its_own_business(): void
    {
        $ownId = $this->makeNamedBusinessId('Own Center');

        $this->actingAsAdmin($ownId);

        $this->putJson("/v1/sys/business/update/{$ownId}", ['name' => 'Own Center Renamed'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Own Center Renamed');
    }

    public function test_tenant_admin_cannot_create_a_business(): void
    {
        $this->actingAsAdmin($this->makeNamedBusinessId('Own Center'));

        $this->postJson('/v1/sys/business/create', [
            'business_code' => 'NEW001',
            'name' => 'Sneaky Center',
            'prefix' => 'NEW',
            'email' => 'sneaky@hana.edu.vn',
            'status' => 'active',
        ])->assertJsonPath('code', 403);

        $this->assertDatabaseMissing('sys_business', ['business_code' => 'NEW001']);
    }

    public function test_tenant_admin_cannot_delete_another_business(): void
    {
        $ownId = $this->makeNamedBusinessId('Own Center');
        $otherId = $this->makeNamedBusinessId('Rival Center');

        $this->actingAsAdmin($ownId);

        $this->deleteJson("/v1/sys/business/delete/{$otherId}")->assertJsonPath('code', 403);

        $this->assertDatabaseHas('sys_business', ['id' => $otherId, 'deleted_at' => null]);
    }

    public function test_superadmin_still_sees_every_business(): void
    {
        $ownId = $this->makeNamedBusinessId('Own Center');
        $otherId = $this->makeNamedBusinessId('Rival Center');

        config(['constants.administrator_usernames' => 'superop']);
        $this->actingAsApi(
            $this->makeUser(true, $this->makeRoleId($ownId), $ownId, ['username' => 'superop'])
        );

        $ids = array_column(
            $this->getJson('/v1/sys/business/list')->assertStatus(200)->json('data.items'),
            'id'
        );

        $this->assertContains($ownId, $ids);
        $this->assertContains($otherId, $ids);

        $this->getJson("/v1/sys/business/detail/{$otherId}")
            ->assertStatus(200)
            ->assertJsonPath('data.business.id', $otherId);
    }
}
