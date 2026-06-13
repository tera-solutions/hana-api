<?php

namespace Tests\Feature;

use Database\Seeders\LeadPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LeadStudentTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $leadId;

    private int $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LeadPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId($this->businessId);
        $this->leadId = $this->makeLeadId();
        $this->studentId = $this->makeStudentId();
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

    private function makeStudentId(string $status = 'active'): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'STD_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'student_id' => $this->studentId,
            'relationship' => 'father',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson("/v1/crm/lead/{$this->leadId}/student/list")->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson("/v1/crm/lead/{$this->leadId}/student/list")->assertJsonPath('code', 403);
    }

    public function test_can_link_student(): void
    {
        $this->actingAsAdmin();

        $this->postJson("/v1/crm/lead/{$this->leadId}/student/add", $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.relationship', 'father');

        $this->assertDatabaseHas('crm_lead_students', [
            'lead_id' => $this->leadId,
            'student_id' => $this->studentId,
            'relationship' => 'father',
        ]);
    }

    public function test_add_validates_required_student(): void
    {
        $this->actingAsAdmin();

        $this->postJson("/v1/crm/lead/{$this->leadId}/student/add", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('student_id');
    }

    public function test_add_rejects_inactive_student(): void
    {
        $this->actingAsAdmin();

        $inactive = $this->makeStudentId('dropped');

        $this->postJson("/v1/crm/lead/{$this->leadId}/student/add", $this->payload(['student_id' => $inactive]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('student_id');
    }

    public function test_add_rejects_duplicate_student_in_same_lead(): void
    {
        $this->actingAsAdmin();

        $this->postJson("/v1/crm/lead/{$this->leadId}/student/add", $this->payload())->assertStatus(200);

        $this->postJson("/v1/crm/lead/{$this->leadId}/student/add", $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('student_id');
    }

    public function test_can_list_links(): void
    {
        $this->actingAsAdmin();

        $this->postJson("/v1/crm/lead/{$this->leadId}/student/add", $this->payload())->assertStatus(200);

        $this->getJson("/v1/crm/lead/{$this->leadId}/student/list")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.student.id', $this->studentId);
    }

    public function test_update_changes_relationship_but_not_student(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson("/v1/crm/lead/{$this->leadId}/student/add", $this->payload())->json('data.id');

        $otherStudent = $this->makeStudentId();

        $this->putJson("/v1/crm/lead/{$this->leadId}/student/update/{$id}", [
            'relationship' => 'mother',
            'student_id' => $otherStudent,
        ])->assertStatus(200)->assertJsonPath('data.relationship', 'mother');

        $this->assertDatabaseHas('crm_lead_students', [
            'id' => $id,
            'student_id' => $this->studentId, // unchanged
            'relationship' => 'mother',
        ]);
    }

    public function test_can_unlink_student(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson("/v1/crm/lead/{$this->leadId}/student/add", $this->payload())->json('data.id');

        $this->deleteJson("/v1/crm/lead/{$this->leadId}/student/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('crm_lead_students', ['id' => $id]);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson("/v1/crm/lead/{$this->leadId}/student/add", $this->payload())->json('data.id');

        $this->assertDatabaseHas('crm_lead_students', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
