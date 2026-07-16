<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\System\Subscription\Models\Subscription;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class SuperadminDashboardTest extends TestCase
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

    private function makePackageId(float $price): int
    {
        return DB::table('sys_packages')->insertGetId([
            'package_code' => 'PKG_'.strtoupper(uniqid()),
            'name' => 'Plan '.$price,
            'price' => $price,
            'billing_cycle' => 'month',
            'features' => json_encode([]),
            'limits' => json_encode([]),
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subscribe(int $businessId, int $packageId, float $price, string $status = Subscription::STATUS_ACTIVE): void
    {
        DB::table('sys_subscriptions')->insert([
            'business_id' => $businessId,
            'package_id' => $packageId,
            'price' => $price,
            'billing_cycle' => 'month',
            'status' => $status,
            'started_at' => now()->toDateString(),
            'expires_at' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_non_superadmin_is_forbidden(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/v1/sys/superadmin/dashboard')->assertJsonPath('code', 403);
    }

    public function test_dashboard_aggregates_tenants_subscriptions_and_mrr(): void
    {
        $paidPackage = $this->makePackageId(200000);
        $trialPackage = $this->makePackageId(0);

        // Two active tenants: one paid, one trial.
        $bizPaid = $this->makeBusinessId('active');
        $this->subscribe($bizPaid, $paidPackage, 200000);

        $bizTrial = $this->makeBusinessId('active');
        $this->subscribe($bizTrial, $trialPackage, 0);

        // One suspended tenant with an expired subscription.
        $bizSuspended = $this->makeBusinessId('suspended');
        $this->subscribe($bizSuspended, $paidPackage, 200000, Subscription::STATUS_EXPIRED);

        $this->actingAsSuperadmin();

        $res = $this->getJson('/v1/sys/superadmin/dashboard')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // 3 seeded businesses + the superadmin's own.
        $this->assertGreaterThanOrEqual(3, $res->json('data.tenants.total'));
        $this->assertSame(1, $res->json('data.tenants.suspended'));

        $this->assertSame(2, $res->json('data.subscriptions.active_total'));
        $this->assertSame(1, $res->json('data.subscriptions.trial'));
        $this->assertSame(1, $res->json('data.subscriptions.paid'));
        $this->assertSame(1, $res->json('data.subscriptions.expired'));

        // MRR counts only the active paid plan.
        $this->assertEquals(200000, $res->json('data.revenue.mrr'));

        $res->assertJsonStructure([
            'data' => [
                'tenants' => ['total', 'active', 'suspended', 'inactive', 'new_this_month'],
                'subscriptions' => ['active_total', 'trial', 'paid', 'expired'],
                'revenue' => ['mrr', 'invoices_paid_total'],
                'plans',
                'tenants_by_month',
            ],
        ]);
    }
}
