<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    /** @var array<int, string> */
    private array $tmpFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    /** Write a CSV under public/ and register it as an uploaded media file. */
    private function uploadCsv(string $contents): int
    {
        $dir = public_path('storage/uploads');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $name = 'vimport_'.uniqid().'.csv';
        $relative = 'storage/uploads/'.$name;
        $absolute = public_path($relative);

        file_put_contents($absolute, $contents);
        $this->tmpFiles[] = $absolute;

        return DB::table('media')->insertGetId([
            'file_path' => $relative,
            'file_name' => $name,
            'file_type' => 'text/csv',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeBranchId(): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'BR_'.strtoupper(uniqid()),
            'address' => '1 Test St',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeParentId(): int
    {
        return DB::table('crm_parents')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'PAR_'.strtoupper(uniqid()),
            'name' => 'Parent '.uniqid(),
            'phone' => '0922222222',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'promotion_name' => 'Summer 2026',
            'promotion_type' => 'discount',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'discount_type' => 'percent',
            'discount_value' => 10,
            'max_discount' => 500000,
        ], $overrides);
    }

    private function createPromotion(array $overrides = []): int
    {
        return $this->postJson('/v1/fin/promotion/create', $this->payload($overrides))->json('data.id');
    }

    private function createActivePromotion(array $overrides = []): int
    {
        $id = $this->createPromotion($overrides);
        $this->postJson("/v1/fin/promotion/activate/{$id}")->assertStatus(200);

        return $id;
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/promotion/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/fin/promotion/list')->assertJsonPath('code', 403);
    }

    public function test_create_starts_as_draft_with_generated_code(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/promotion/create', $this->payload([
            'rules' => [['rule_type' => 'min_order', 'rule_value' => '5000000']],
            'rewards' => [['reward_type' => 'discount', 'reward_value' => '10']],
        ]))
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.promotion_code', 'PROMO000001')
            ->assertJsonCount(1, 'data.rules')
            ->assertJsonCount(1, 'data.rewards');
    }

    public function test_lifecycle_activate_pause_close(): void
    {
        $this->actingAsAdmin();

        $id = $this->createPromotion();

        $this->postJson("/v1/fin/promotion/activate/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->postJson("/v1/fin/promotion/pause/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'paused');

        $this->postJson("/v1/fin/promotion/close/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'closed');
    }

    public function test_cannot_pause_a_draft(): void
    {
        $this->actingAsAdmin();

        $id = $this->createPromotion();

        $this->postJson("/v1/fin/promotion/pause/{$id}")
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Chỉ có thể tạm ngưng chương trình đang chạy.');
    }

    public function test_apply_percentage_discount_caps_at_max(): void
    {
        $this->actingAsAdmin();

        $id = $this->createActivePromotion();

        // 10% of 6,000,000 = 600,000 but max_discount is 500,000.
        $this->postJson('/v1/fin/promotion/apply', ['promotion_id' => $id, 'amount' => 6000000])
            ->assertStatus(200)
            ->assertJsonPath('data.discount_amount', 500000)
            ->assertJsonPath('data.final_amount', 5500000);
    }

    public function test_apply_rejected_when_promotion_not_active(): void
    {
        $this->actingAsAdmin();

        $id = $this->createPromotion(); // still draft

        $this->postJson('/v1/fin/promotion/apply', ['promotion_id' => $id, 'amount' => 1000000])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Chương trình khuyến mãi chưa được kích hoạt.');
    }

    public function test_apply_enforces_minimum_order(): void
    {
        $this->actingAsAdmin();

        $id = $this->createActivePromotion([
            'rules' => [['rule_type' => 'min_order', 'rule_value' => '5000000']],
        ]);

        $this->postJson('/v1/fin/promotion/apply', ['promotion_id' => $id, 'amount' => 1000000])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Đơn hàng chưa đạt giá trị tối thiểu để áp dụng khuyến mãi.');
    }

    public function test_generate_validate_and_apply_voucher_consumes_use(): void
    {
        $this->actingAsAdmin();

        $id = $this->createActivePromotion(['promotion_type' => 'voucher']);

        $code = $this->postJson("/v1/fin/promotion/generate-vouchers/{$id}", ['quantity' => 1])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->json('data.0.voucher_code');

        $this->postJson('/v1/fin/promotion/voucher/validate', ['voucher_code' => $code])
            ->assertStatus(200)
            ->assertJsonPath('data.voucher_code', $code);

        $this->postJson('/v1/fin/promotion/apply', ['voucher_code' => $code, 'amount' => 2000000])
            ->assertStatus(200)
            ->assertJsonPath('data.discount_amount', 200000);

        // Single-use voucher is now spent.
        $this->postJson('/v1/fin/promotion/voucher/validate', ['voucher_code' => $code])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Voucher đã được sử dụng hết.');
    }

    public function test_import_vouchers_persists_valid_rows_and_reports_failures(): void
    {
        $this->actingAsAdmin();

        $id = $this->createActivePromotion(['promotion_type' => 'voucher']);

        // One valid row, one duplicate of it, one missing the code.
        $csv = implode("\n", [
            'Voucher Code,Usage Limit,Expired At',
            'HANA-A,3,2026-08-31',
            'HANA-A,1,2026-08-31',
            ',2,2026-08-31',
        ]);

        $fileId = $this->uploadCsv($csv);

        $this->postJson("/v1/fin/promotion/import-vouchers/{$id}", ['file_id' => $fileId, 'file_name' => 'vouchers.csv'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.imported', 1)
            ->assertJsonCount(2, 'data.failed')
            ->assertJsonPath('data.failed.0.row', 3)
            ->assertJsonPath('data.failed.1.row', 4);

        $this->assertDatabaseHas('fin_vouchers', [
            'promotion_id' => $id,
            'voucher_code' => 'HANA-A',
            'usage_limit' => 3,
            'status' => 'active',
        ]);
    }

    public function test_import_vouchers_rejects_unknown_file(): void
    {
        $this->actingAsAdmin();

        $id = $this->createActivePromotion(['promotion_type' => 'voucher']);

        $this->postJson("/v1/fin/promotion/import-vouchers/{$id}", ['file_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file_id']);
    }

    public function test_referral_create_and_reward(): void
    {
        $this->actingAsAdmin();

        $referrer = $this->makeParentId();
        $referred = $this->makeParentId();

        $id = $this->postJson('/v1/fin/promotion/referral/create', [
            'referrer_parent_id' => $referrer,
            'referred_parent_id' => $referred,
            'reward_amount' => 100000,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending')
            ->json('data.id');

        $this->postJson("/v1/fin/promotion/referral/reward/{$id}", ['reward_amount' => 100000])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'rewarded')
            ->assertJsonPath('data.reward_amount', '100000.00');
    }

    public function test_referral_rejects_same_parent(): void
    {
        $this->actingAsAdmin();

        $parent = $this->makeParentId();

        $this->postJson('/v1/fin/promotion/referral/create', [
            'referrer_parent_id' => $parent,
            'referred_parent_id' => $parent,
        ])->assertStatus(422);
    }
}
