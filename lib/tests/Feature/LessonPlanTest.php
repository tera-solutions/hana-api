<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsLessonPlans;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LessonPlanTest extends TestCase
{
    use BuildsLessonPlans;
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLessonPlanContext();
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/lesson-plan/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/lesson-plan/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_plan_as_draft(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/lesson-plan/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('data.plan_code', 'KIDS_STARTER_V1')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.total_lessons', 0);

        $this->assertDatabaseHas('edu_lesson_plans', ['plan_code' => 'KIDS_STARTER_V1', 'status' => 'draft']);
    }

    public function test_create_validation(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/lesson-plan/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['plan_code', 'plan_name', 'course_id']);

        $this->postJson('/v1/edu/lesson-plan/create', $this->payload())->assertStatus(200);
        $this->postJson('/v1/edu/lesson-plan/create', $this->payload(['plan_name' => 'Other']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('plan_code');
    }

    public function test_list_filters_by_status(): void
    {
        $this->actingAsAdmin();

        $this->createPlan();
        $this->createPlan(['plan_code' => 'TOEIC_V1', 'plan_name' => 'TOEIC']);

        $this->getJson('/v1/edu/lesson-plan/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/edu/lesson-plan/list?search=TOEIC')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_detail_returns_lessons_and_usage(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $this->addLesson($planId, 'Alphabet');

        $this->getJson("/v1/edu/lesson-plan/detail/{$planId}")
            ->assertStatus(200)
            ->assertJsonPath('data.plan.id', $planId)
            ->assertJsonPath('data.plan.total_lessons', 1)
            ->assertJsonPath('data.usage.classes', 0)
            ->assertJsonStructure(['data' => ['plan' => ['lessons', 'versions'], 'usage' => ['classes']]]);
    }

    public function test_publish_requires_a_lesson(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();

        $this->postJson("/v1/edu/lesson-plan/publish/{$planId}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->addLesson($planId, 'Alphabet');

        $this->postJson("/v1/edu/lesson-plan/publish/{$planId}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('edu_lesson_plan_versions', ['lesson_plan_id' => $planId, 'version' => 1]);
    }

    public function test_published_plan_cannot_be_updated_directly(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $this->addLesson($planId, 'Alphabet');
        $this->postJson("/v1/edu/lesson-plan/publish/{$planId}")->assertStatus(200);

        $this->putJson("/v1/edu/lesson-plan/update/{$planId}", ['plan_name' => 'New name'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_clone_deep_copies_lessons_with_new_version(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $this->addLesson($planId, 'Alphabet');
        $this->addLesson($planId, 'Numbers');

        $response = $this->postJson("/v1/edu/lesson-plan/clone/{$planId}", ['plan_code' => 'KIDS_STARTER_V2'])
            ->assertStatus(200)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.status', 'draft');

        $cloneId = $response->json('data.id');

        $this->assertDatabaseHas('edu_lesson_plans', ['id' => $cloneId, 'plan_code' => 'KIDS_STARTER_V2', 'total_lessons' => 2]);
        $this->assertSame(2, DB::table('edu_lesson_plan_lessons')->where('lesson_plan_id', $cloneId)->count());
    }

    public function test_plan_used_by_class_cannot_be_edited(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        DB::table('edu_classes')->where('id', $this->makeClassId())->update(['lesson_plan_id' => $planId]);

        $this->putJson("/v1/edu/lesson-plan/update/{$planId}", ['plan_name' => 'New'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_archive_plan(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();

        $this->postJson("/v1/edu/lesson-plan/archive/{$planId}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'archived');
    }

    public function test_restore_plan_returns_archived_plan_to_draft(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $this->postJson("/v1/edu/lesson-plan/archive/{$planId}")->assertStatus(200);

        $this->postJson("/v1/edu/lesson-plan/restore/{$planId}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'draft');

        // Only archived plans can be restored.
        $this->postJson("/v1/edu/lesson-plan/restore/{$planId}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    // ── Teacher access ───────────────────────────────────────────────────────

    /** A non-admin, hr_teachers-linked user (a teacher) in the acting business. */
    private function actingAsTeacher(array $permissions = []): int
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, $permissions);
        $user = $this->makeUser(false, $roleId, $this->businessId);

        $teacherId = DB::table('hr_teachers')->insertGetId([
            'user_id' => $user->id,
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Teacher '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsApi($user);

        return $teacherId;
    }

    public function test_teacher_can_manage_own_plan_with_no_linked_class(): void
    {
        $this->actingAsTeacher(['lesson_plan.list', 'lesson_plan.view', 'lesson_plan.create', 'lesson_plan.update']);

        // No class links this teacher to the course — only authorship does.
        $planId = $this->postJson('/v1/edu/lesson-plan/create', $this->payload())->json('data.id');

        $this->getJson("/v1/edu/lesson-plan/detail/{$planId}")
            ->assertStatus(200)
            ->assertJsonPath('data.plan.id', $planId);

        $this->putJson("/v1/edu/lesson-plan/update/{$planId}", ['plan_name' => 'Renamed by author'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_teacher_can_access_any_plan_in_their_business(): void
    {
        // Teacher-level row scoping was retired: a teacher with the lesson-plan
        // permissions may reach any plan in their business, including one
        // authored by a colleague. Cross-business access stays blocked by
        // tenant isolation (see TenantIsolationTest).
        $this->actingAsAdmin();
        $planId = $this->createPlan();

        $this->actingAsTeacher(['lesson_plan.list', 'lesson_plan.view', 'lesson_plan.update']);

        $this->getJson("/v1/edu/lesson-plan/detail/{$planId}")
            ->assertStatus(200)
            ->assertJsonPath('data.plan.id', $planId);
        $this->putJson("/v1/edu/lesson-plan/update/{$planId}", ['plan_name' => 'Edited by colleague'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
