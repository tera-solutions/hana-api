<?php

namespace Tests\Feature;

use Database\Seeders\LeadPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LeadGuardianTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $leadId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LeadPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId($this->businessId);
        $this->leadId = $this->makeLeadId();
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

    private function makeLeadId(): int
    {
        return DB::table('crm_leads')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'LEAD_'.strtoupper(uniqid()),
            'name' => 'Lead '.uniqid(),
            'phone' => '09'.random_int(10000000, 99999999),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Robert Smith',
            'relationship' => 'Bố',
            'phone' => '0922222222',
            'email' => 'robert@example.com',
        ], $overrides);
    }

    private function addGuardian(array $overrides = []): int
    {
        return $this->postJson("/v1/crm/lead/{$this->leadId}/guardian/add", $this->payload($overrides))
            ->json('data.id');
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/v1/crm/lead/{$this->leadId}/guardian/list")->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson("/v1/crm/lead/{$this->leadId}/guardian/list")->assertJsonPath('code', 403);
    }

    public function test_can_add_guardian(): void
    {
        $this->actingAsAdmin();

        $this->postJson("/v1/crm/lead/{$this->leadId}/guardian/add", $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.relationship', 'Bố');

        $this->assertDatabaseHas('crm_lead_guardians', [
            'lead_id' => $this->leadId,
            'phone' => '0922222222',
            'relationship' => 'Bố',
        ]);
    }

    public function test_add_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson("/v1/crm/lead/{$this->leadId}/guardian/add", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'relationship', 'phone']);
    }

    public function test_add_rejects_duplicate_phone_within_lead(): void
    {
        $this->actingAsAdmin();

        $this->addGuardian();

        $this->postJson("/v1/crm/lead/{$this->leadId}/guardian/add", $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_can_list_guardians(): void
    {
        $this->actingAsAdmin();

        $this->addGuardian();
        $this->addGuardian(['full_name' => 'Jane Smith', 'relationship' => 'Mẹ', 'phone' => '0933333333']);

        $this->getJson("/v1/crm/lead/{$this->leadId}/guardian/list")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_can_update_guardian(): void
    {
        $this->actingAsAdmin();

        $id = $this->addGuardian();

        $this->putJson("/v1/crm/lead/{$this->leadId}/guardian/update/{$id}", [
            'full_name' => 'Robert Smith Jr',
            'relationship' => 'Người giám hộ',
        ])->assertStatus(200)->assertJsonPath('data.full_name', 'Robert Smith Jr');

        $this->assertDatabaseHas('crm_lead_guardians', [
            'id' => $id,
            'full_name' => 'Robert Smith Jr',
            'relationship' => 'Người giám hộ',
        ]);
    }

    public function test_delete_blocked_when_last_guardian(): void
    {
        $this->actingAsAdmin();

        $id = $this->addGuardian();

        $this->deleteJson("/v1/crm/lead/{$this->leadId}/guardian/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('crm_lead_guardians', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_delete_allowed_when_another_guardian_exists(): void
    {
        $this->actingAsAdmin();

        $first = $this->addGuardian();
        $this->addGuardian(['full_name' => 'Jane Smith', 'relationship' => 'Mẹ', 'phone' => '0933333333']);

        $this->deleteJson("/v1/crm/lead/{$this->leadId}/guardian/delete/{$first}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('crm_lead_guardians', ['id' => $first]);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->addGuardian();

        $this->assertDatabaseHas('crm_lead_guardians', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
