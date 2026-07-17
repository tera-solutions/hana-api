<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class BankAccountTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function actingAsTeacher(array $permissions = ['bank_account.view', 'bank_account.update']): array
    {
        $businessId = $this->makeBusinessId();
        $roleId = $this->makeRoleId($businessId);
        $this->grantPermissions($roleId, $permissions);
        $user = $this->makeUser(false, $roleId, $businessId);

        $teacherId = DB::table('hr_teachers')->insertGetId([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Teacher '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsApi($user);

        return [$user, $teacherId];
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/bank-account/me')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/fin/bank-account/me')->assertJsonPath('code', 403);
    }

    public function test_me_returns_null_when_not_set(): void
    {
        $this->actingAsTeacher();

        $this->getJson('/v1/fin/bank-account/me')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);
    }

    public function test_teacher_can_set_own_bank_account(): void
    {
        [, $teacherId] = $this->actingAsTeacher();

        $this->putJson('/v1/fin/bank-account/me', [
            'bank_name' => 'Vietcombank',
            'bank_account_number' => '0123456789',
            'bank_account_holder' => 'Nguyen Van A',
        ])->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bank_account_number', '0123456789');

        $this->assertDatabaseHas('fin_bank_accounts', [
            'owner_type' => 'App\Modules\HR\Teacher\Models\Teacher',
            'owner_id' => $teacherId,
            'bank_account_number' => '0123456789',
        ]);

        $this->getJson('/v1/fin/bank-account/me')
            ->assertStatus(200)
            ->assertJsonPath('data.bank_account_holder', 'Nguyen Van A');
    }

    public function test_updating_again_overwrites_the_single_account(): void
    {
        $this->actingAsTeacher();

        $this->putJson('/v1/fin/bank-account/me', [
            'bank_name' => 'Vietcombank',
            'bank_account_number' => '0123456789',
            'bank_account_holder' => 'Nguyen Van A',
        ])->assertStatus(200);

        $this->putJson('/v1/fin/bank-account/me', [
            'bank_name' => 'Techcombank',
            'bank_account_number' => '9999999999',
            'bank_account_holder' => 'Nguyen Van B',
        ])->assertStatus(200)
            ->assertJsonPath('data.bank_account_number', '9999999999');

        $this->assertSame(1, DB::table('fin_bank_accounts')->count());
    }

    public function test_validates_required_fields(): void
    {
        $this->actingAsTeacher();

        $this->putJson('/v1/fin/bank-account/me', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bank_name', 'bank_account_number', 'bank_account_holder']);
    }

    public function test_requires_teacher_profile(): void
    {
        $businessId = $this->makeBusinessId();
        $roleId = $this->makeRoleId($businessId);
        $this->grantPermissions($roleId, ['bank_account.view', 'bank_account.update']);
        $this->actingAsApi($this->makeUser(false, $roleId, $businessId));

        $this->putJson('/v1/fin/bank-account/me', [
            'bank_name' => 'Vietcombank',
            'bank_account_number' => '0123456789',
            'bank_account_holder' => 'Nguyen Van A',
        ])->assertStatus(200)->assertJsonPath('success', false);
    }
}
