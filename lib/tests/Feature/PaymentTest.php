<?php

namespace Tests\Feature;

use Database\Seeders\AccountPermissionSeeder;
use Database\Seeders\PaymentPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaymentPermissionSeeder::class);
        $this->seed(AccountPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId($this->businessId);
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

    private function makeAccountId(float $balance = 0): int
    {
        return $this->postJson('/v1/fin/account/create', [
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'name' => 'Quỹ tiền mặt',
            'type' => 'cash',
            'balance' => $balance,
        ])->json('data.id');
    }

    private function makeReceivableInvoiceId(): int
    {
        return $this->postJson('/v1/fin/invoice/create', [
            'invoice_type' => 'receivable',
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'partner_type' => 'student',
            'items' => [['name' => 'Học phí', 'quantity' => 1, 'unit_price' => 1500000]],
        ])->json('data.id');
    }

    private function paymentPayload(array $overrides = []): array
    {
        return array_merge([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'payment_direction' => 'in',
            'payment_type' => 'tuition_payment',
            'partner_type' => 'student',
            'amount' => 1000000,
            'method' => 'cash',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/payment/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/fin/payment/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_draft_payment_and_generates_no(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/fin/payment/create', $this->paymentPayload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.payment_direction', 'in');

        $this->assertNotEmpty($response->json('data.payment_no'));
        $this->assertDatabaseHas('fin_payments', ['id' => $response->json('data.id'), 'status' => 'draft']);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/payment/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['business_id', 'payment_direction', 'amount']);
    }

    public function test_confirm_moves_account_balance(): void
    {
        $this->actingAsAdmin();

        $accountId = $this->makeAccountId(0);
        $id = $this->postJson('/v1/fin/payment/create', $this->paymentPayload(['account_id' => $accountId]))->json('data.id');

        $this->postJson("/v1/fin/payment/confirm/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        // IN increases the fund balance (BR-03).
        $this->getJson("/v1/fin/account/detail/{$accountId}")
            ->assertJsonPath('data.balance', '1000000.00');
    }

    public function test_confirm_applies_allocation_to_invoice(): void
    {
        $this->actingAsAdmin();

        $invoiceId = $this->makeReceivableInvoiceId();
        $id = $this->postJson('/v1/fin/payment/create', $this->paymentPayload([
            'amount' => 1500000,
            'invoice_id' => $invoiceId,
            'allocations' => [['invoice_id' => $invoiceId, 'allocated_amount' => 1500000]],
        ]))->json('data.id');

        $this->postJson("/v1/fin/payment/confirm/{$id}")->assertStatus(200);

        // The invoice is fully settled by the confirmed payment.
        $this->getJson("/v1/fin/invoice/detail/{$invoiceId}")
            ->assertJsonPath('data.invoice.status', 'paid')
            ->assertJsonPath('data.invoice.balance_amount', '0.00');
    }

    public function test_cannot_cancel_confirmed_payment(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/payment/create', $this->paymentPayload())->json('data.id');
        $this->postJson("/v1/fin/payment/confirm/{$id}")->assertStatus(200);

        $this->postJson("/v1/fin/payment/cancel/{$id}", ['reason' => 'x'])
            ->assertJsonPath('success', false);
    }

    public function test_cancel_draft_payment(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/payment/create', $this->paymentPayload())->json('data.id');

        $this->postJson("/v1/fin/payment/cancel/{$id}", ['reason' => 'Nhập nhầm'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_reverse_confirmed_payment(): void
    {
        $this->actingAsAdmin();

        $accountId = $this->makeAccountId(0);
        $id = $this->postJson('/v1/fin/payment/create', $this->paymentPayload(['account_id' => $accountId]))->json('data.id');
        $this->postJson("/v1/fin/payment/confirm/{$id}")->assertStatus(200);

        $this->postJson("/v1/fin/payment/reverse/{$id}", ['reason' => 'Sai số tiền'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'reversed');

        // Balance is back to 0 and an opposite (OUT) counter transaction exists.
        $this->getJson("/v1/fin/account/detail/{$accountId}")->assertJsonPath('data.balance', '0.00');
        $this->assertDatabaseHas('fin_payments', ['parent_payment_id' => $id, 'payment_direction' => 'out']);
    }

    public function test_refund_confirmed_payment(): void
    {
        $this->actingAsAdmin();

        $accountId = $this->makeAccountId(0);
        $id = $this->postJson('/v1/fin/payment/create', $this->paymentPayload(['account_id' => $accountId]))->json('data.id');
        $this->postJson("/v1/fin/payment/confirm/{$id}")->assertStatus(200);

        $this->postJson("/v1/fin/payment/refund/{$id}", ['amount' => 400000, 'reason' => 'Thu thừa'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'refunded');

        // 1,000,000 in − 400,000 refunded = 600,000 left in the fund.
        $this->getJson("/v1/fin/account/detail/{$accountId}")->assertJsonPath('data.balance', '600000.00');
        $this->assertDatabaseHas('fin_payments', ['parent_payment_id' => $id, 'payment_direction' => 'out', 'amount' => 400000]);
    }

    public function test_update_blocked_after_confirm(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/payment/create', $this->paymentPayload())->json('data.id');
        $this->postJson("/v1/fin/payment/confirm/{$id}")->assertStatus(200);

        $this->putJson("/v1/fin/payment/update/{$id}", ['description' => 'late edit'])
            ->assertJsonPath('success', false);
    }

    public function test_list_filters_by_direction(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/payment/create', $this->paymentPayload(['payment_direction' => 'in']))->assertStatus(200);
        $this->postJson('/v1/fin/payment/create', $this->paymentPayload(['payment_direction' => 'out', 'payment_type' => 'rent_payment']))->assertStatus(200);

        $this->getJson('/v1/fin/payment/list?payment_direction=out')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.payment_direction', 'out');
    }

    public function test_detail_returns_payment_and_history(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/payment/create', $this->paymentPayload())->json('data.id');

        $this->getJson("/v1/fin/payment/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.payment.id', $id)
            ->assertJsonPath('data.histories.0.action', 'created');
    }
}
