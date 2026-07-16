<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\System\Subscription\Models\Subscription;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class SuperadminTenantTest extends TestCase
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

    private function actingAsTenantOwner(int $bizId): User
    {
        return $this->actingAsApi($this->makeUser(true, $this->makeRoleId($bizId), $bizId));
    }

    private function makePackageId(): int
    {
        return DB::table('sys_packages')->insertGetId([
            'package_code' => 'PKG_'.strtoupper(uniqid()),
            'name' => 'Gói Nâng cao',
            'price' => 299000,
            'billing_cycle' => 'month',
            'features' => json_encode([]),
            'limits' => json_encode(['students' => 100]),
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_non_superadmin_is_forbidden(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/v1/sys/superadmin/tenant/list')
            ->assertJsonPath('code', 403);
    }

    public function test_superadmin_lists_all_tenants(): void
    {
        $bizA = $this->makeBusinessId();
        $bizB = $this->makeBusinessId();

        $this->actingAsSuperadmin();

        $res = $this->getJson('/v1/sys/superadmin/tenant/list')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $ids = collect($res->json('data.items'))->pluck('id');
        $this->assertTrue($ids->contains($bizA));
        $this->assertTrue($ids->contains($bizB));
    }

    public function test_detail_returns_stats_and_subscription_shape(): void
    {
        $bizA = $this->makeBusinessId();
        $this->actingAsSuperadmin();

        $this->getJson("/v1/sys/superadmin/tenant/detail/{$bizA}")
            ->assertStatus(200)
            ->assertJsonPath('data.business.id', $bizA)
            ->assertJsonStructure([
                'data' => [
                    'business' => ['id', 'name', 'status'],
                    'statistics' => ['total_students', 'total_parents', 'total_teachers'],
                    'invoices',
                ],
            ]);
    }

    public function test_suspend_blocks_tenant_and_activate_restores(): void
    {
        $bizA = $this->makeBusinessId();

        // Tenant owner can use the API before suspension.
        $this->actingAsTenantOwner($bizA);
        $this->getJson('/v1/edu/student/list')->assertStatus(200)->assertJsonPath('success', true);

        // Superadmin suspends the tenant.
        $this->actingAsSuperadmin();
        $this->postJson("/v1/sys/superadmin/tenant/suspend/{$bizA}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        // Now every endpoint is blocked for that tenant.
        $this->actingAsTenantOwner($bizA);
        $this->getJson('/v1/edu/student/list')->assertJsonPath('code', 403);

        // Reactivation restores access.
        $this->actingAsSuperadmin();
        $this->postJson("/v1/sys/superadmin/tenant/activate/{$bizA}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->actingAsTenantOwner($bizA);
        $this->getJson('/v1/edu/student/list')->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_assign_extend_and_cancel_subscription(): void
    {
        $bizA = $this->makeBusinessId();
        $packageId = $this->makePackageId();

        $this->actingAsSuperadmin();

        // Assign a plan → active subscription + paid invoice.
        $assign = $this->postJson("/v1/sys/superadmin/tenant/{$bizA}/subscription/assign", [
            'package_id' => $packageId,
            'billing_cycle' => 'month',
            'payment_method' => 'bank_transfer',
        ])->assertStatus(200)->assertJsonPath('data.status', Subscription::STATUS_ACTIVE);

        $subId = $assign->json('data.id');
        $expiresAfterAssign = $assign->json('data.expires_at');

        $this->assertDatabaseHas('sys_subscription_invoices', [
            'business_id' => $bizA,
            'status' => 'paid',
        ]);

        // Extend by 3 months → expiry moves later.
        $extend = $this->postJson("/v1/sys/superadmin/tenant/{$bizA}/subscription/extend", ['months' => 3])
            ->assertStatus(200)
            ->assertJsonPath('data.id', $subId);
        $this->assertGreaterThan(
            Carbon::parse($expiresAfterAssign),
            Carbon::parse($extend->json('data.expires_at')),
        );

        // Cancel → status cancelled.
        $this->postJson("/v1/sys/superadmin/tenant/{$bizA}/subscription/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', Subscription::STATUS_CANCELLED);

        $this->assertDatabaseHas('sys_subscriptions', [
            'id' => $subId,
            'status' => Subscription::STATUS_CANCELLED,
        ]);
    }

    public function test_extend_without_active_subscription_errors(): void
    {
        $bizA = $this->makeBusinessId();
        $this->actingAsSuperadmin();

        $this->postJson("/v1/sys/superadmin/tenant/{$bizA}/subscription/extend", ['months' => 2])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }
}
