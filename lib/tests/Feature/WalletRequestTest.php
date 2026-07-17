<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class WalletRequestTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function actingAsTeacher(array $permissions = ['wallet_request.list', 'wallet_request.view', 'wallet_request.create', 'wallet_request.cancel']): User
    {
        $businessId = $this->makeBusinessId();
        $roleId = $this->makeRoleId($businessId);
        $this->grantPermissions($roleId, $permissions);
        $user = $this->makeUser(false, $roleId, $businessId);

        DB::table('hr_teachers')->insertGetId([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Teacher '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->actingAsApi($user);
    }

    /** Same as actingAsTeacher(), plus a saved HR profile bank account. */
    private function actingAsTeacherWithBankAccount(array $permissions = ['wallet_request.list', 'wallet_request.view', 'wallet_request.create', 'wallet_request.cancel']): User
    {
        $user = $this->actingAsTeacher($permissions);

        $teacherId = DB::table('hr_teachers')->where('user_id', $user->id)->value('id');

        DB::table('fin_bank_accounts')->insert([
            'owner_type' => 'App\Modules\HR\Teacher\Models\Teacher',
            'owner_id' => $teacherId,
            'bank_name' => 'Vietcombank',
            'bank_account_number' => '0123456789',
            'bank_account_holder' => 'Nguyen Van A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/wallet-request/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 100000])
            ->assertJsonPath('code', 403);
    }

    public function test_create_deposit_request_lazily_creates_wallet(): void
    {
        $teacher = $this->actingAsTeacherWithBankAccount();

        $this->assertDatabaseMissing('fin_wallets', ['owner_type' => 'teacher', 'owner_id' => $teacher->id]);

        $response = $this->postJson('/v1/fin/wallet-request/create', [
            'request_type' => 'deposit',
            'amount' => 500000,
            'note' => 'Nạp thu nhập',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.request_type', 'deposit');

        $this->assertDatabaseHas('fin_wallets', ['owner_type' => 'teacher', 'owner_id' => $teacher->id]);
        $this->assertDatabaseHas('fin_wallet_requests', ['amount' => 500000, 'status' => 'pending']);
    }

    public function test_create_withdraw_requires_bank_account(): void
    {
        $this->actingAsTeacher();

        $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'withdraw', 'amount' => 200000])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_create_deposit_requires_bank_account(): void
    {
        $this->actingAsTeacher();

        $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 200000])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_create_withdraw_with_bank_account_succeeds(): void
    {
        $this->actingAsTeacherWithBankAccount();

        $response = $this->postJson('/v1/fin/wallet-request/create', [
            'request_type' => 'withdraw',
            'amount' => 200000,
        ])->assertStatus(200)->assertJsonPath('data.status', 'pending');

        $this->assertSame('0123456789', $response->json('data.bank_account.bank_account_number'));
    }

    public function test_second_request_reuses_same_wallet(): void
    {
        $teacher = $this->actingAsTeacherWithBankAccount();

        $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 100000])
            ->assertStatus(200);
        $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 200000])
            ->assertStatus(200);

        $this->assertSame(
            1,
            DB::table('fin_wallets')->where('owner_type', 'teacher')->where('owner_id', $teacher->id)->count(),
        );
    }

    public function test_teacher_cannot_approve_own_request(): void
    {
        $this->actingAsTeacherWithBankAccount();

        $id = $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 100000])
            ->json('data.id');

        $this->postJson("/v1/fin/wallet-request/approve/{$id}")->assertJsonPath('code', 403);
    }

    public function test_admin_approve_then_complete_deposit_credits_wallet(): void
    {
        $teacher = $this->actingAsTeacherWithBankAccount();
        $id = $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 300000])
            ->json('data.id');

        $this->actingAsAdmin($teacher->business_id);

        $this->postJson("/v1/fin/wallet-request/approve/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $walletId = DB::table('fin_wallets')->where('owner_type', 'teacher')->where('owner_id', $teacher->id)->value('id');
        $this->assertEquals(0, DB::table('fin_wallets')->where('id', $walletId)->value('available_balance'));

        $this->postJson("/v1/fin/wallet-request/complete/{$id}", ['note' => 'Đã nhận tiền mặt'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertEquals(300000, DB::table('fin_wallets')->where('id', $walletId)->value('available_balance'));
        $this->assertDatabaseHas('fin_wallet_transactions', ['wallet_id' => $walletId, 'transaction_type' => 'deposit', 'amount' => 300000]);
    }

    public function test_complete_withdraw_debits_wallet(): void
    {
        $teacher = $this->actingAsTeacherWithBankAccount();
        $walletId = DB::table('fin_wallets')->insertGetId([
            'business_id' => $teacher->business_id,
            'owner_type' => 'teacher',
            'owner_id' => $teacher->id,
            'wallet_code' => 'WAL_'.strtoupper(uniqid()),
            'available_balance' => 500000,
            'bonus_balance' => 0,
            'frozen_balance' => 0,
            'currency' => 'VND',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = $this->postJson('/v1/fin/wallet-request/create', [
            'request_type' => 'withdraw',
            'amount' => 150000,
        ])->json('data.id');

        $this->actingAsAdmin($teacher->business_id);
        $this->postJson("/v1/fin/wallet-request/approve/{$id}")->assertStatus(200);
        $this->postJson("/v1/fin/wallet-request/complete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertEquals(350000, DB::table('fin_wallets')->where('id', $walletId)->value('available_balance'));
    }

    public function test_cannot_complete_before_approve(): void
    {
        $teacher = $this->actingAsTeacherWithBankAccount();
        $id = $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 100000])
            ->json('data.id');

        $this->actingAsAdmin($teacher->business_id);

        $this->postJson("/v1/fin/wallet-request/complete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_reject_records_reason(): void
    {
        $teacher = $this->actingAsTeacherWithBankAccount();
        $id = $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 100000])
            ->json('data.id');

        $this->actingAsAdmin($teacher->business_id);

        $this->postJson("/v1/fin/wallet-request/reject/{$id}", ['reject_reason' => 'Thiếu thông tin'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.reject_reason', 'Thiếu thông tin');
    }

    public function test_teacher_can_cancel_own_pending_request(): void
    {
        $this->actingAsTeacherWithBankAccount();
        $id = $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 100000])
            ->json('data.id');

        $this->postJson("/v1/fin/wallet-request/cancel/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cannot_cancel_after_approved(): void
    {
        $teacher = $this->actingAsTeacherWithBankAccount();
        $id = $this->postJson('/v1/fin/wallet-request/create', ['request_type' => 'deposit', 'amount' => 100000])
            ->json('data.id');

        $this->actingAsAdmin($teacher->business_id);
        $this->postJson("/v1/fin/wallet-request/approve/{$id}")->assertStatus(200);

        $this->postJson("/v1/fin/wallet-request/cancel/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }
}
