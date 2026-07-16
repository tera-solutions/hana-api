<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class SuperadminPackageTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function actingAsSuperadmin(): User
    {
        config(['constants.administrator_usernames' => 'superop']);

        $user = User::where('username', 'superop')->first();

        if (! $user) {
            $bizId = $this->makeBusinessId();
            $user = $this->makeUser(false, $this->makeRoleId($bizId), $bizId, ['username' => 'superop']);
        }

        return $this->actingAsApi($user);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'package_code' => 'PKG-'.strtoupper(uniqid()),
            'name' => 'Gói Test',
            'description' => 'Mô tả',
            'price' => 199000,
            'billing_cycle' => 'month',
            'features' => ['Tính năng A', 'Tính năng B'],
            'limits' => ['students' => 100, 'parents' => 100, 'classes' => 10],
            'badge' => 'Mới',
            'is_active' => true,
            'sort_order' => 5,
        ], $overrides);
    }

    public function test_non_superadmin_cannot_manage_packages(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/v1/sys/superadmin/package/list')->assertJsonPath('code', 403);
        $this->postJson('/v1/sys/superadmin/package/create', $this->payload())->assertJsonPath('code', 403);
    }

    public function test_superadmin_creates_a_package_with_limits(): void
    {
        $this->actingAsSuperadmin();

        $payload = $this->payload();
        $res = $this->postJson('/v1/sys/superadmin/package/create', $payload)
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.package_code', $payload['package_code'])
            ->assertJsonPath('data.limits.students', 100);

        $this->assertDatabaseHas('sys_packages', [
            'id' => $res->json('data.id'),
            'package_code' => $payload['package_code'],
            'price' => 199000,
        ]);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $this->actingAsSuperadmin();

        $payload = $this->payload();
        $this->postJson('/v1/sys/superadmin/package/create', $payload)->assertStatus(200);

        $this->postJson('/v1/sys/superadmin/package/create', $this->payload(['package_code' => $payload['package_code']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['package_code']);
    }

    public function test_update_changes_price_and_limits_but_not_code(): void
    {
        $this->actingAsSuperadmin();

        $created = $this->postJson('/v1/sys/superadmin/package/create', $this->payload())->json('data');

        $this->putJson("/v1/sys/superadmin/package/update/{$created['id']}", [
            'price' => 250000,
            'limits' => ['students' => 200, 'parents' => 150],
            'package_code' => 'HACK-ATTEMPT',
        ])->assertStatus(200)
            ->assertJsonPath('data.price', 250000)
            ->assertJsonPath('data.limits.students', 200)
            ->assertJsonPath('data.package_code', $created['package_code']);
    }

    public function test_deactivate_hides_from_tenant_catalog(): void
    {
        $this->actingAsSuperadmin();

        $created = $this->postJson('/v1/sys/superadmin/package/create', $this->payload())->json('data');

        $this->postJson("/v1/sys/superadmin/package/deactivate/{$created['id']}")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // Superadmin still sees it.
        $adminIds = collect($this->getJson('/v1/sys/superadmin/package/list')->json('data.items'))->pluck('id');
        $this->assertTrue($adminIds->contains($created['id']));

        // The tenant-facing catalog (a regular admin) does not.
        $this->actingAsAdmin();
        $tenantIds = collect($this->getJson('/v1/sys/package/list')->json('data.items'))->pluck('id');
        $this->assertFalse($tenantIds->contains($created['id']));
    }

    public function test_superadmin_list_includes_inactive_packages(): void
    {
        $inactiveId = DB::table('sys_packages')->insertGetId([
            'package_code' => 'PKG-HIDDEN-'.strtoupper(uniqid()),
            'name' => 'Ẩn',
            'price' => 0,
            'billing_cycle' => 'month',
            'features' => json_encode([]),
            'limits' => json_encode([]),
            'is_active' => false,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsSuperadmin();

        $ids = collect($this->getJson('/v1/sys/superadmin/package/list')->json('data.items'))->pluck('id');
        $this->assertTrue($ids->contains($inactiveId));
    }
}
