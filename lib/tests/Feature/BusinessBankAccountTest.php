<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class BusinessBankAccountTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'bank_name' => 'MB Bank',
            'bank_code' => '970422',
            'account_number' => '0123456789',
            'account_holder' => 'CONG TY TNHH HANA ENGLISH',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/business-bank-account/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/fin/business-bank-account/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_account(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/business-bank-account/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.bank_code', '970422')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_default', false);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/business-bank-account/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bank_name', 'bank_code', 'account_number', 'account_holder']);
    }

    public function test_is_scoped_to_acting_business(): void
    {
        $bizA = $this->businessId;
        $bizB = $this->makeBusinessId();

        $this->actingAsAdmin($bizA);
        $this->postJson('/v1/fin/business-bank-account/create', $this->payload())->assertStatus(200);

        $this->actingAsAdmin($bizB);
        $this->getJson('/v1/fin/business-bank-account/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }

    public function test_setting_default_clears_other_defaults(): void
    {
        $this->actingAsAdmin();

        $firstId = $this->postJson('/v1/fin/business-bank-account/create', $this->payload(['is_default' => true]))
            ->json('data.id');
        $secondId = $this->postJson('/v1/fin/business-bank-account/create', $this->payload([
            'bank_name' => 'Vietcombank',
            'bank_code' => '970436',
            'is_default' => true,
        ]))->json('data.id');

        $this->assertDatabaseHas('fin_business_bank_accounts', ['id' => $secondId, 'is_default' => true]);
        $this->assertDatabaseHas('fin_business_bank_accounts', ['id' => $firstId, 'is_default' => false]);
    }

    public function test_suspend_clears_default_and_restore(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/business-bank-account/create', $this->payload(['is_default' => true]))
            ->json('data.id');

        $this->postJson("/v1/fin/business-bank-account/suspend/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.is_default', false);

        $this->postJson("/v1/fin/business-bank-account/suspend/{$id}")->assertJsonPath('success', false);

        $this->postJson("/v1/fin/business-bank-account/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_update_changes_fields(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/business-bank-account/create', $this->payload())->json('data.id');

        $this->putJson("/v1/fin/business-bank-account/update/{$id}", ['account_holder' => 'HANA ENGLISH JSC'])
            ->assertStatus(200)
            ->assertJsonPath('data.account_holder', 'HANA ENGLISH JSC');
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/business-bank-account/create', $this->payload())->json('data.id');

        $this->assertDatabaseHas('fin_business_bank_accounts', [
            'id' => $id,
            'business_id' => $this->businessId,
            'created_by' => $admin->id,
        ]);
    }
}
