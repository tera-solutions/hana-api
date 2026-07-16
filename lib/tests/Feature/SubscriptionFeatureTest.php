<?php

namespace Tests\Feature;

use App\Modules\System\Subscription\Models\Subscription;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class SubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function makePackageId(?array $featureKeys): int
    {
        return DB::table('sys_packages')->insertGetId([
            'package_code' => 'PKG_'.strtoupper(uniqid()),
            'name' => 'Test Package',
            'price' => 100000,
            'billing_cycle' => 'month',
            'features' => json_encode([]),
            'feature_keys' => $featureKeys === null ? null : json_encode($featureKeys),
            'limits' => json_encode([]),
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subscribe(int $businessId, int $packageId): void
    {
        DB::table('sys_subscriptions')->insert([
            'business_id' => $businessId,
            'package_id' => $packageId,
            'price' => 100000,
            'billing_cycle' => 'month',
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now()->toDateString(),
            'expires_at' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_plan_without_assignments_feature_is_blocked(): void
    {
        $businessId = $this->makeBusinessId();
        $this->subscribe($businessId, $this->makePackageId([]));

        $this->actingAsAdmin($businessId);

        $this->getJson('/v1/edu/assignment/list')
            ->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 402)
            ->assertJsonPath('errors.feature', 'assignments');
    }

    public function test_plan_with_assignments_feature_is_allowed(): void
    {
        $businessId = $this->makeBusinessId();
        $this->subscribe($businessId, $this->makePackageId(['assignments', 'messaging']));

        $this->actingAsAdmin($businessId);

        $this->getJson('/v1/edu/assignment/list')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_grandfathered_business_keeps_all_features(): void
    {
        $businessId = $this->makeBusinessId();

        $this->actingAsAdmin($businessId);

        // No subscription row at all → treated as unlimited/all features.
        $this->getJson('/v1/edu/assignment/list')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_null_feature_keys_grant_all_features(): void
    {
        $businessId = $this->makeBusinessId();
        // A subscription whose package has no configured feature set.
        $this->subscribe($businessId, $this->makePackageId(null));

        $this->actingAsAdmin($businessId);

        $this->getJson('/v1/edu/assignment/list')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_messaging_gate_blocks_broadcast_but_not_receiving(): void
    {
        $businessId = $this->makeBusinessId();
        $this->subscribe($businessId, $this->makePackageId([]));

        $this->actingAsAdmin($businessId);

        // Receiving notifications stays open on every plan.
        $this->getJson('/v1/sys/notification/list')->assertStatus(200);

        // Broadcasting is gated behind the "messaging" feature (middleware runs
        // before validation, so an empty body still short-circuits to 402).
        $this->postJson('/v1/sys/notification/create', [])
            ->assertStatus(200)
            ->assertJsonPath('code', 402)
            ->assertJsonPath('errors.feature', 'messaging');
    }
}
