<?php

namespace Tests\Feature;

use App\Modules\System\Subscription\Models\Subscription;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class SubscriptionQuotaTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function makeBranchId(int $businessId): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'CN_'.strtoupper(uniqid()),
            'address' => '123 Le Loi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeLevelId(): int
    {
        return DB::table('edu_levels')->insertGetId([
            'level_code' => 'A1_'.strtoupper(uniqid()),
            'level_name' => 'A1',
            'level_order' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makePackageId(?array $limits): int
    {
        return DB::table('sys_packages')->insertGetId([
            'package_code' => 'PKG_'.strtoupper(uniqid()),
            'name' => 'Test Package',
            'price' => 100000,
            'billing_cycle' => 'month',
            'features' => json_encode([]),
            'limits' => json_encode($limits),
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subscribe(int $businessId, int $packageId, string $status = Subscription::STATUS_ACTIVE, ?string $expiresAt = null): void
    {
        DB::table('sys_subscriptions')->insert([
            'business_id' => $businessId,
            'package_id' => $packageId,
            'price' => 100000,
            'billing_cycle' => 'month',
            'status' => $status,
            'started_at' => now()->subMonth()->toDateString(),
            'expires_at' => $expiresAt ?? now()->addMonth()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function studentPayload(int $businessId, int $branchId, int $levelId): array
    {
        return [
            'name' => 'Nguyen Van A',
            'dob' => '2010-05-12',
            'gender' => 'male',
            'email' => 'a-'.uniqid().'@gmail.com',
            'phone' => '0901234567',
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'level_id' => $levelId,
            'enrollment_date' => '2026-06-01',
            'address' => '123 Le Loi',
            'province' => 'Ho Chi Minh',
            'district' => 'District 7',
        ];
    }

    private function parentPayload(int $businessId, int $branchId): array
    {
        return [
            'name' => 'Robert Smith',
            'gender' => 'male',
            'phone' => '09'.substr((string) microtime(true), -8),
            'email' => 'parent-'.uniqid().'@example.com',
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'address' => '123 Le Loi',
        ];
    }

    public function test_parent_quota_blocks_create_beyond_plan_limit(): void
    {
        $businessId = $this->makeBusinessId();
        $packageId = $this->makePackageId(['parents' => 1]);
        $this->subscribe($businessId, $packageId);

        $this->actingAsAdmin($businessId);
        $branchId = $this->makeBranchId($businessId);

        // First parent fills the quota.
        $this->postJson('/v1/crm/parent/create', $this->parentPayload($businessId, $branchId))
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Second exceeds it and is rejected by the quota guard.
        $this->postJson('/v1/crm/parent/create', $this->parentPayload($businessId, $branchId))
            ->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 402);
    }

    public function test_parent_unlimited_plan_allows_many_creates(): void
    {
        $businessId = $this->makeBusinessId();
        $packageId = $this->makePackageId(['parents' => null]);
        $this->subscribe($businessId, $packageId);

        $this->actingAsAdmin($businessId);
        $branchId = $this->makeBranchId($businessId);

        foreach (range(1, 3) as $_) {
            $this->postJson('/v1/crm/parent/create', $this->parentPayload($businessId, $branchId))
                ->assertStatus(200)
                ->assertJsonPath('success', true);
        }
    }

    public function test_quota_blocks_create_beyond_plan_limit(): void
    {
        $businessId = $this->makeBusinessId();
        $packageId = $this->makePackageId(['students' => 1]);
        $this->subscribe($businessId, $packageId);

        $this->actingAsAdmin($businessId);
        $branchId = $this->makeBranchId($businessId);
        $levelId = $this->makeLevelId();

        // First student fills the quota.
        $this->postJson('/v1/edu/student/create', $this->studentPayload($businessId, $branchId, $levelId))
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Second exceeds it and is rejected by the quota guard.
        $this->postJson('/v1/edu/student/create', $this->studentPayload($businessId, $branchId, $levelId))
            ->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 402);
    }

    public function test_unlimited_plan_allows_many_creates(): void
    {
        $businessId = $this->makeBusinessId();
        $packageId = $this->makePackageId(['students' => null]);
        $this->subscribe($businessId, $packageId);

        $this->actingAsAdmin($businessId);
        $branchId = $this->makeBranchId($businessId);
        $levelId = $this->makeLevelId();

        foreach (range(1, 3) as $_) {
            $this->postJson('/v1/edu/student/create', $this->studentPayload($businessId, $branchId, $levelId))
                ->assertStatus(200)
                ->assertJsonPath('success', true);
        }
    }

    public function test_expired_subscription_blocks_create(): void
    {
        $businessId = $this->makeBusinessId();
        $packageId = $this->makePackageId(['students' => 100]);
        // Active status but past its expiry date.
        $this->subscribe($businessId, $packageId, Subscription::STATUS_ACTIVE, now()->subDay()->toDateString());

        $this->actingAsAdmin($businessId);
        $branchId = $this->makeBranchId($businessId);
        $levelId = $this->makeLevelId();

        $this->postJson('/v1/edu/student/create', $this->studentPayload($businessId, $branchId, $levelId))
            ->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 402);
    }

    public function test_business_without_subscription_is_grandfathered(): void
    {
        $businessId = $this->makeBusinessId();

        $this->actingAsAdmin($businessId);
        $branchId = $this->makeBranchId($businessId);
        $levelId = $this->makeLevelId();

        $this->postJson('/v1/edu/student/create', $this->studentPayload($businessId, $branchId, $levelId))
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_expire_command_flips_lapsed_subscriptions(): void
    {
        $businessId = $this->makeBusinessId();
        $packageId = $this->makePackageId(['students' => 10]);

        $this->subscribe($businessId, $packageId, Subscription::STATUS_ACTIVE, now()->subDay()->toDateString());
        $this->subscribe($this->makeBusinessId(), $packageId, Subscription::STATUS_ACTIVE, now()->addDay()->toDateString());

        $this->artisan('subscriptions:expire')->assertExitCode(0);

        $this->assertDatabaseHas('sys_subscriptions', [
            'business_id' => $businessId,
            'status' => Subscription::STATUS_EXPIRED,
        ]);
        // The one expiring tomorrow stays active.
        $this->assertDatabaseHas('sys_subscriptions', [
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }
}
