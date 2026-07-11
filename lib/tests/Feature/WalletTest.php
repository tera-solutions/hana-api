<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function makeWallet(User $user, array $overrides = []): int
    {
        static $owner = 0;
        $owner++;

        return DB::table('fin_wallets')->insertGetId(array_merge([
            'business_id' => $user->business_id,
            'wallet_code' => 'WAL_'.strtoupper(uniqid()),
            'owner_type' => 'parent',
            'owner_id' => $owner,
            'available_balance' => 0,
            'bonus_balance' => 0,
            'frozen_balance' => 0,
            'currency' => 'VND',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function makeBranchId(int $businessId): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'BR_'.strtoupper(uniqid()),
            'address' => '1 Test St',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeStudentId(int $businessId): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $businessId,
            'branch_id' => $this->makeBranchId($businessId),
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeInvoiceId(int $businessId): int
    {
        return DB::table('fin_invoices')->insertGetId([
            'business_id' => $businessId,
            'student_id' => $this->makeStudentId($businessId),
            'code' => 'INV_'.strtoupper(uniqid()),
            'total' => 2000000,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makePaymentId(int $businessId): int
    {
        return DB::table('fin_payments')->insertGetId([
            'business_id' => $businessId,
            'student_id' => $this->makeStudentId($businessId),
            'amount' => 1000000,
            'method' => 'cash',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/wallet/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/fin/wallet/list')->assertJsonPath('code', 403);
    }

    public function test_list_and_filter_by_status(): void
    {
        $user = $this->actingAsAdmin();

        $this->makeWallet($user, ['status' => 'active']);
        $this->makeWallet($user, ['status' => 'locked']);

        $this->getJson('/v1/fin/wallet/list?status=locked')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.status', 'locked');
    }

    public function test_deposit_credits_available_and_writes_ledger(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user);

        $this->postJson('/v1/fin/wallet/deposit', ['wallet_id' => $walletId, 'amount' => 500000, 'payment_method' => 'cash'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction_type', 'deposit')
            ->assertJsonPath('data.balance_before', '0.00')
            ->assertJsonPath('data.balance_after', '500000.00')
            ->assertJsonPath('data.transaction_code', 'WTX000001');

        $this->assertDatabaseHas('fin_wallets', ['id' => $walletId, 'available_balance' => 500000]);
    }

    public function test_deposit_rejects_non_positive_amount(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user);

        $this->postJson('/v1/fin/wallet/deposit', ['wallet_id' => $walletId, 'amount' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_detail_returns_wallet_with_transactions(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user);
        $this->postJson('/v1/fin/wallet/deposit', ['wallet_id' => $walletId, 'amount' => 100000]);

        $this->getJson("/v1/fin/wallet/detail/{$walletId}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $walletId)
            ->assertJsonPath('data.available_balance', '100000.00')
            ->assertJsonCount(1, 'data.transactions');
    }

    public function test_lock_blocks_mutations_and_unlock_restores(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user, ['available_balance' => 100000]);

        $this->postJson("/v1/fin/wallet/lock/{$walletId}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'locked');

        // BR012: locked wallet blocks deposit / payment / refund.
        $this->postJson('/v1/fin/wallet/deposit', ['wallet_id' => $walletId, 'amount' => 50000])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Ví đang bị khóa.');

        $this->postJson("/v1/fin/wallet/unlock/{$walletId}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->postJson('/v1/fin/wallet/deposit', ['wallet_id' => $walletId, 'amount' => 50000])
            ->assertJsonPath('success', true);
    }

    public function test_payment_debits_and_cannot_go_negative(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user, ['available_balance' => 100000]);

        $this->postJson('/v1/fin/wallet/payment', ['wallet_id' => $walletId, 'amount' => 60000])
            ->assertStatus(200)
            ->assertJsonPath('data.transaction_type', 'payment')
            ->assertJsonPath('data.balance_after', '40000.00');

        // BR006: balance cannot go negative.
        $this->postJson('/v1/fin/wallet/payment', ['wallet_id' => $walletId, 'amount' => 99999])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Số dư ví không đủ.');
    }

    public function test_payment_spends_bonus_before_available(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user, ['available_balance' => 50000, 'bonus_balance' => 30000]);

        // BR007: 40,000 draws 30,000 bonus first, then 10,000 available.
        $this->postJson('/v1/fin/wallet/payment', ['wallet_id' => $walletId, 'amount' => 40000])
            ->assertStatus(200)
            ->assertJsonPath('data.balance_after', '40000.00');

        $this->assertDatabaseHas('fin_wallets', [
            'id' => $walletId,
            'bonus_balance' => 0,
            'available_balance' => 40000,
        ]);
    }

    public function test_refund_references_original_and_respects_paid_amount(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user, ['available_balance' => 100000]);

        $paymentTxnId = $this->postJson('/v1/fin/wallet/payment', ['wallet_id' => $walletId, 'amount' => 60000])
            ->json('data.id');

        // BR009: cannot refund more than paid.
        $this->postJson('/v1/fin/wallet/refund', ['wallet_id' => $walletId, 'amount' => 80000, 'reference_transaction_id' => $paymentTxnId])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Số tiền hoàn vượt quá số đã thanh toán.');

        $this->postJson('/v1/fin/wallet/refund', ['wallet_id' => $walletId, 'amount' => 60000, 'reference_transaction_id' => $paymentTxnId])
            ->assertStatus(200)
            ->assertJsonPath('data.transaction_type', 'refund')
            ->assertJsonPath('data.balance_after', '100000.00');
    }

    public function test_refund_rejects_non_payment_reference(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user);

        // The original is a deposit, not a payment (BR008).
        $depositTxnId = $this->postJson('/v1/fin/wallet/deposit', ['wallet_id' => $walletId, 'amount' => 50000])
            ->json('data.id');

        $this->postJson('/v1/fin/wallet/refund', ['wallet_id' => $walletId, 'amount' => 10000, 'reference_transaction_id' => $depositTxnId])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Chỉ có thể hoàn tiền cho giao dịch thanh toán.');
    }

    public function test_adjustment_increase_decrease_and_reason_required(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user, ['available_balance' => 100000]);

        $this->postJson('/v1/fin/wallet/adjustment', ['wallet_id' => $walletId, 'adjustment_type' => 'increase', 'amount' => 25000, 'reason' => 'Bù sai lệch'])
            ->assertStatus(200)
            ->assertJsonPath('data.balance_after', '125000.00');

        $this->postJson('/v1/fin/wallet/adjustment', ['wallet_id' => $walletId, 'adjustment_type' => 'decrease', 'amount' => 25000, 'reason' => 'Thu hồi'])
            ->assertStatus(200)
            ->assertJsonPath('data.balance_after', '100000.00');

        // The adjustment is also recorded in fin_wallet_adjustments (BR011).
        $this->assertDatabaseHas('fin_wallet_adjustments', ['wallet_id' => $walletId, 'adjustment_type' => 'increase', 'reason' => 'Bù sai lệch']);

        // BR010: reason is required.
        $this->postJson('/v1/fin/wallet/adjustment', ['wallet_id' => $walletId, 'adjustment_type' => 'increase', 'amount' => 1000])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_transaction_history_lists_entries(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user);

        $this->postJson('/v1/fin/wallet/deposit', ['wallet_id' => $walletId, 'amount' => 100000]);
        $this->postJson('/v1/fin/wallet/payment', ['wallet_id' => $walletId, 'amount' => 30000]);

        $this->getJson("/v1/fin/wallet/transactions?wallet_id={$walletId}")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson("/v1/fin/wallet/transactions?wallet_id={$walletId}&transaction_type=payment")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.transaction_type', 'payment');
    }

    public function test_record_from_invoice_debits_and_links_invoice(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user, ['available_balance' => 100000]);
        $invoiceId = $this->makeInvoiceId($user->business_id);

        $this->postJson('/v1/fin/wallet/from-invoice', ['wallet_id' => $walletId, 'invoice_id' => $invoiceId, 'amount' => 40000])
            ->assertStatus(200)
            ->assertJsonPath('data.transaction_type', 'payment')
            ->assertJsonPath('data.reference_type', 'invoice')
            ->assertJsonPath('data.reference_id', $invoiceId)
            ->assertJsonPath('data.balance_after', '60000.00');
    }

    public function test_record_from_payment_credits_and_links_payment(): void
    {
        $user = $this->actingAsAdmin();
        $walletId = $this->makeWallet($user);
        $paymentId = $this->makePaymentId($user->business_id);

        $this->postJson('/v1/fin/wallet/from-payment', ['wallet_id' => $walletId, 'payment_id' => $paymentId, 'amount' => 70000])
            ->assertStatus(200)
            ->assertJsonPath('data.transaction_type', 'deposit')
            ->assertJsonPath('data.reference_type', 'payment')
            ->assertJsonPath('data.reference_id', $paymentId)
            ->assertJsonPath('data.balance_after', '70000.00');
    }

    public function test_creating_a_parent_auto_creates_one_wallet(): void
    {
        $user = $this->actingAsAdmin();
        $branchId = $this->makeBranchId($user->business_id);

        $parentId = $this->postJson('/v1/crm/parent/create', [
            'name' => 'Phụ huynh Test',
            'phone' => '0900000001',
            'business_id' => $user->business_id,
            'branch_id' => $branchId,
        ])
            ->assertStatus(200)
            ->json('data.id');

        // BR002 + BR001: exactly one wallet for the new parent.
        $this->assertDatabaseHas('fin_wallets', [
            'business_id' => $user->business_id,
            'owner_type' => 'parent',
            'owner_id' => $parentId,
        ]);
        $this->assertSame(1, DB::table('fin_wallets')->where('owner_type', 'parent')->where('owner_id', $parentId)->count());
    }
}
