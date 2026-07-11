<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class AccountTest extends TestCase
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
            'business_id' => $this->businessId,
            'name' => 'Quỹ tiền mặt',
            'type' => 'cash',
            'balance' => 0,
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/account/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/fin/account/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_account_and_generates_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/fin/account/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'cash')
            ->assertJsonPath('data.status', 'active');

        $this->assertNotEmpty($response->json('data.code'));
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/account/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['business_id', 'name', 'type']);
    }

    public function test_update_does_not_change_balance(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/account/create', $this->payload(['balance' => 500000]))->json('data.id');

        $this->putJson("/v1/fin/account/update/{$id}", ['name' => 'Quỹ VCB', 'balance' => 999999])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Quỹ VCB')
            ->assertJsonPath('data.balance', '500000.00');
    }

    public function test_suspend_and_restore(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/account/create', $this->payload())->json('data.id');

        $this->postJson("/v1/fin/account/suspend/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        $this->postJson("/v1/fin/account/suspend/{$id}")->assertJsonPath('success', false);

        $this->postJson("/v1/fin/account/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_list_filters_by_type(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/account/create', $this->payload(['type' => 'cash']))->assertStatus(200);
        $this->postJson('/v1/fin/account/create', $this->payload(['type' => 'bank', 'name' => 'VCB']))->assertStatus(200);

        $this->getJson('/v1/fin/account/list?type=bank')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.type', 'bank');
    }
}
