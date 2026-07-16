<?php

namespace Tests\Feature;

use App\Modules\System\Subscription\Models\Subscription;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class ProfileSubscriptionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function makePackageId(array $featureKeys): int
    {
        return DB::table('sys_packages')->insertGetId([
            'package_code' => 'PKG_'.strtoupper(uniqid()),
            'name' => 'Gói Test',
            'price' => 299000,
            'billing_cycle' => 'month',
            'features' => json_encode([]),
            'feature_keys' => json_encode($featureKeys),
            'limits' => json_encode(['students' => 500]),
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
            'price' => 299000,
            'billing_cycle' => 'month',
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now()->toDateString(),
            'expires_at' => now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getProfile()
    {
        return $this->withHeaders(['device-code' => 'test-device'])
            ->getJson('/api/auth/profile');
    }

    public function test_profile_includes_current_subscription_with_feature_keys(): void
    {
        $businessId = $this->makeBusinessId();
        $this->subscribe($businessId, $this->makePackageId(['assignments', 'advanced_reports']));

        $this->actingAsAdmin($businessId);

        $this->getProfile()
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subscription.status', Subscription::STATUS_ACTIVE)
            ->assertJsonPath('data.subscription.package.feature_keys', ['assignments', 'advanced_reports'])
            ->assertJsonPath('data.subscription.package.limits.students', 500);
    }

    public function test_profile_subscription_null_when_grandfathered(): void
    {
        $businessId = $this->makeBusinessId();
        $this->actingAsAdmin($businessId);

        $this->getProfile()
            ->assertStatus(200)
            ->assertJsonPath('data.subscription', null);
    }

    public function test_profile_requires_device_code(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/auth/profile')->assertJsonPath('code', 500);
    }
}
