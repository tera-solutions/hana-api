<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class EvaluationCriteriaTemplateTest extends TestCase
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
            'evaluation_type' => 'teacher',
            'name' => 'Đánh giá giáo viên chuẩn',
            'criteria' => ['expertise', 'teaching_method', 'communication'],
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/evaluation-criteria-template/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/evaluation-criteria-template/list')->assertJsonPath('code', 403);
    }

    public function test_admin_can_create_shared_template(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload(['is_shared' => true]))
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_shared', true)
            ->assertJsonPath('data.criteria.0', 'expertise');
    }

    public function test_create_rejects_criteria_not_allowed_for_the_type(): void
    {
        $this->actingAsAdmin($this->businessId);

        // "Chuyên môn" isn't one of EvaluationType::Teacher->criteria() — a
        // template must only ever contain keys evaluation/create will accept.
        $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload(['criteria' => ['Chuyên môn']]))
            ->assertStatus(422);
    }

    public function test_update_rejects_criteria_not_allowed_for_the_types_existing_record(): void
    {
        $this->actingAsAdmin($this->businessId);
        $id = $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload())->json('data.id');

        $this->putJson("/v1/edu/evaluation-criteria-template/update/{$id}", ['criteria' => ['not_a_real_key']])
            ->assertStatus(422);
    }

    public function test_non_admin_create_is_forced_private(): void
    {
        $this->actingAsTeacher();

        $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload(['is_shared' => true]))
            ->assertStatus(200)
            ->assertJsonPath('data.is_shared', false);
    }

    public function test_teacher_sees_shared_templates_and_own_private_ones_only(): void
    {
        $this->actingAsAdmin($this->businessId);
        $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload(['name' => 'Shared', 'is_shared' => true]))
            ->assertStatus(200);

        [$teacherA] = $this->actingAsTeacher();
        $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload(['name' => 'A private']))
            ->assertStatus(200);

        $this->getJson('/v1/edu/evaluation-criteria-template/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        [$teacherB] = $this->actingAsTeacher();
        $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload(['name' => 'B private']))
            ->assertStatus(200);

        // B sees the shared one + their own, not A's private one.
        $response = $this->getJson('/v1/edu/evaluation-criteria-template/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);
        $names = collect($response->json('data.items'))->pluck('name')->all();
        $this->assertContains('Shared', $names);
        $this->assertContains('B private', $names);
        $this->assertNotContains('A private', $names);
    }

    public function test_teacher_cannot_update_another_teachers_private_template(): void
    {
        [$teacherA] = $this->actingAsTeacher();
        $id = $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload())->json('data.id');

        $this->actingAsTeacher();

        $this->putJson("/v1/edu/evaluation-criteria-template/update/{$id}", ['name' => 'Hacked'])
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_update_any_template(): void
    {
        [$teacherA] = $this->actingAsTeacher();
        $id = $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload())->json('data.id');

        $this->actingAsAdmin($this->businessId);

        $this->putJson("/v1/edu/evaluation-criteria-template/update/{$id}", ['name' => 'Admin edited'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Admin edited');
    }

    public function test_suspend_and_restore(): void
    {
        $this->actingAsAdmin($this->businessId);
        $id = $this->postJson('/v1/edu/evaluation-criteria-template/create', $this->payload())->json('data.id');

        $this->postJson("/v1/edu/evaluation-criteria-template/suspend/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        $this->postJson("/v1/edu/evaluation-criteria-template/suspend/{$id}")->assertJsonPath('success', false);

        $this->postJson("/v1/edu/evaluation-criteria-template/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');
    }

    /** @return array{0: int} [hr_teachers id] */
    private function actingAsTeacher(): array
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, [
            'evaluation_criteria_template.list',
            'evaluation_criteria_template.view',
            'evaluation_criteria_template.create',
            'evaluation_criteria_template.update',
            'evaluation_criteria_template.suspend',
            'evaluation_criteria_template.restore',
        ]);
        $user = $this->makeUser(false, $roleId, $this->businessId);
        $this->actingAsApi($user);

        return [$user->id];
    }
}
