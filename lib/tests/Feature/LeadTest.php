<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $ownerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId($this->businessId);
        $this->ownerId = $this->makeUser(false, $this->makeRoleId($this->businessId), $this->businessId)->id;
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

    private function makeStudentId(string $status = 'active'): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'STD_'.strtoupper(uniqid()),
            'name' => 'Linked Student',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeTagId(): int
    {
        return DB::table('crm_tags')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Tag '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Course '.uniqid(),
            'code' => 'CRS_'.strtoupper(uniqid()),
            'duration_minutes' => 90,
            'price_per_lesson' => 250000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mary Smith',
            'gender' => 'female',
            'phone' => '0911111111',
            'email' => 'mary@example.com',
            'source' => 'facebook',
            'owner_id' => $this->ownerId,
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'note' => 'Interested in IELTS.',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/crm/lead/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/crm/lead/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_lead_and_generates_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/crm/lead/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'LEAD000001')
            ->assertJsonPath('data.status', 'pending');

        $id = $response->json('data.id');

        $this->assertDatabaseHas('crm_leads', ['id' => $id, 'code' => 'LEAD000001', 'status' => 'pending']);
        $this->assertDatabaseHas('crm_lead_histories', ['lead_id' => $id, 'action' => 'created']);
    }

    public function test_code_increments_per_lead(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/lead/create', $this->payload())
            ->assertJsonPath('data.code', 'LEAD000001');
        $this->postJson('/v1/crm/lead/create', $this->payload(['phone' => '0911222333']))
            ->assertJsonPath('data.code', 'LEAD000002');
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/lead/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'source', 'owner_id']);
    }

    public function test_create_links_guardians_students_tags_and_courses(): void
    {
        $this->actingAsAdmin();

        $studentId = $this->makeStudentId();
        $tagId = $this->makeTagId();
        $courseId = $this->makeCourseId();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload([
            'tag_ids' => [$tagId],
            'course_ids' => [$courseId],
            'guardians' => [
                ['full_name' => 'Robert Smith', 'relationship' => 'Bố', 'phone' => '0922222222', 'email' => 'robert@example.com'],
            ],
            'students' => [
                ['student_id' => $studentId, 'relationship' => 'father'],
            ],
        ]))->assertStatus(200)->json('data.id');

        $this->assertDatabaseHas('crm_lead_guardians', ['lead_id' => $id, 'phone' => '0922222222', 'relationship' => 'Bố']);
        $this->assertDatabaseHas('crm_lead_students', ['lead_id' => $id, 'student_id' => $studentId, 'relationship' => 'father']);
        $this->assertDatabaseHas('crm_lead_tags', ['lead_id' => $id, 'tag_id' => $tagId]);
        $this->assertDatabaseHas('crm_lead_courses', ['lead_id' => $id, 'course_id' => $courseId]);
    }

    public function test_create_rejects_duplicate_phone_of_active_lead(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/lead/create', $this->payload(['phone' => '0911000111']))->assertStatus(200);

        $this->postJson('/v1/crm/lead/create', $this->payload(['phone' => '0911000111']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_phone_can_be_reused_after_lead_is_suspended(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload(['phone' => '0911000222']))->json('data.id');

        $this->postJson("/v1/crm/lead/suspend/{$id}", ['reason' => 'Stopped'])->assertStatus(200);

        // The inactive lead no longer blocks the phone.
        $this->postJson('/v1/crm/lead/create', $this->payload(['phone' => '0911000222']))
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_can_list_and_search_leads(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/crm/lead/create', $this->payload())->assertStatus(200);
        $this->postJson('/v1/crm/lead/create', $this->payload(['name' => 'Unique Prospect', 'phone' => '0911333444']))->assertStatus(200);

        $this->getJson('/v1/crm/lead/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/crm/lead/list?search=Unique')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.name', 'Unique Prospect');
    }

    public function test_detail_returns_lead_and_history(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $this->getJson("/v1/crm/lead/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.lead.id', $id)
            ->assertJsonPath('data.histories.0.action', 'created');
    }

    public function test_update_ignores_immutable_fields(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $this->putJson("/v1/crm/lead/update/{$id}", [
            'name' => 'Renamed',
            'code' => 'HACKED',
            'status' => 'inactive',
        ])->assertStatus(200)->assertJsonPath('data.name', 'Renamed');

        $this->assertDatabaseHas('crm_leads', [
            'id' => $id,
            'name' => 'Renamed',
            'code' => 'LEAD000001',
            'status' => 'pending',
        ]);
    }

    public function test_update_logs_owner_change(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $newOwner = $this->makeUser(false, $this->makeRoleId($this->businessId), $this->businessId)->id;

        $this->putJson("/v1/crm/lead/update/{$id}", ['owner_id' => $newOwner])
            ->assertStatus(200)
            ->assertJsonPath('data.owner_id', $newOwner);

        $this->assertDatabaseHas('crm_lead_histories', [
            'lead_id' => $id,
            'action' => 'owner_changed',
            'to_owner_id' => $newOwner,
        ]);
    }

    public function test_suspend_and_restore_lifecycle(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $this->postJson("/v1/crm/lead/suspend/{$id}", ['reason' => 'No longer interested'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('crm_leads', ['id' => $id, 'status' => 'inactive', 'previous_status' => 'pending']);
        $this->assertDatabaseHas('crm_lead_histories', ['lead_id' => $id, 'action' => 'suspended']);

        // Suspending again is rejected.
        $this->postJson("/v1/crm/lead/suspend/{$id}", ['reason' => 'x'])
            ->assertJsonPath('success', false);

        $this->postJson("/v1/crm/lead/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('crm_lead_histories', ['lead_id' => $id, 'action' => 'restored']);
    }

    public function test_suspend_requires_reason(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $this->postJson("/v1/crm/lead/suspend/{$id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_restore_rejected_when_not_inactive(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $this->postJson("/v1/crm/lead/restore/{$id}")
            ->assertJsonPath('success', false);
    }

    public function test_update_status_moves_lead_through_pipeline(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $this->patchJson("/v1/crm/lead/status/{$id}", ['status' => 'consulting', 'note' => 'Đã hẹn tư vấn 22/07'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'consulting');

        $this->assertDatabaseHas('crm_lead_histories', [
            'lead_id' => $id,
            'action' => 'status_changed',
            'from_status' => 'pending',
            'to_status' => 'consulting',
            'note' => 'Đã hẹn tư vấn 22/07',
        ]);
    }

    public function test_update_status_rejects_inactive_as_target(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $this->patchJson("/v1/crm/lead/status/{$id}", ['status' => 'inactive'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_update_status_rejected_when_lead_is_inactive(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');
        $this->postJson("/v1/crm/lead/suspend/{$id}", ['reason' => 'x'])->assertStatus(200);

        $this->patchJson("/v1/crm/lead/status/{$id}", ['status' => 'verified'])
            ->assertJsonPath('success', false);
    }

    public function test_convert_creates_student_and_links_lead(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $response = $this->postJson("/v1/crm/lead/convert/{$id}", ['dob' => '2016-05-12'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lead_id', $id);

        $studentId = $response->json('data.student_id');

        $this->assertDatabaseHas('edu_students', [
            'id' => $studentId,
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'name' => 'Mary Smith',
        ]);
        $this->assertDatabaseHas('crm_lead_students', ['lead_id' => $id, 'student_id' => $studentId, 'relationship' => 'self']);
        $this->assertDatabaseHas('crm_leads', ['id' => $id, 'status' => 'studying']);
        $this->assertDatabaseHas('crm_lead_histories', ['lead_id' => $id, 'action' => 'converted', 'to_status' => 'studying']);
    }

    public function test_convert_rejects_when_dob_missing(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $this->postJson("/v1/crm/lead/convert/{$id}", [])
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('crm_leads', ['id' => $id, 'status' => 'pending']);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/crm/lead/create', $this->payload())->json('data.id');

        $this->assertDatabaseHas('crm_leads', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
