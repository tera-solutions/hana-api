<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLessonPlans;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LessonPlanLessonActivityTest extends TestCase
{
    use BuildsLessonPlans;
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLessonPlanContext();
    }

    public function test_can_create_activity(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $lessonId = $this->addLesson($planId, 'One');

        $response = $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', [
            'lesson_plan_lesson_id' => $lessonId,
            'title' => 'Warm-up',
            'duration' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Warm-up')
            ->assertJsonPath('data.sort_order', 1);

        $this->assertDatabaseHas('edu_lesson_plan_lesson_activities', [
            'lesson_plan_lesson_id' => $lessonId,
            'title' => 'Warm-up',
        ]);
    }

    public function test_create_appends_sort_order_when_omitted(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $lessonId = $this->addLesson($planId, 'One');

        $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', [
            'lesson_plan_lesson_id' => $lessonId,
            'title' => 'Warm-up',
        ])->assertJsonPath('data.sort_order', 1);

        $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', [
            'lesson_plan_lesson_id' => $lessonId,
            'title' => 'Practice',
        ])->assertJsonPath('data.sort_order', 2);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lesson_plan_lesson_id', 'title']);
    }

    public function test_can_list_and_filter_by_lesson(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $l1 = $this->addLesson($planId, 'One');
        $l2 = $this->addLesson($planId, 'Two');

        $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', ['lesson_plan_lesson_id' => $l1, 'title' => 'A']);
        $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', ['lesson_plan_lesson_id' => $l1, 'title' => 'B']);
        $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', ['lesson_plan_lesson_id' => $l2, 'title' => 'C']);

        $this->getJson('/v1/edu/lesson-plan/lesson-activity/list?lesson_plan_lesson_id='.$l1)
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_can_update_activity(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $lessonId = $this->addLesson($planId, 'One');

        $id = $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', [
            'lesson_plan_lesson_id' => $lessonId,
            'title' => 'Warm-up',
        ])->json('data.id');

        $this->putJson("/v1/edu/lesson-plan/lesson-activity/update/{$id}", ['title' => 'Warm-up updated', 'duration' => 15])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Warm-up updated')
            ->assertJsonPath('data.duration', 15);

        $this->assertDatabaseHas('edu_lesson_plan_lesson_activities', ['id' => $id, 'title' => 'Warm-up updated', 'duration' => 15]);
    }

    public function test_can_delete_activity(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $lessonId = $this->addLesson($planId, 'One');

        $id = $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', [
            'lesson_plan_lesson_id' => $lessonId,
            'title' => 'Warm-up',
        ])->json('data.id');

        $this->deleteJson("/v1/edu/lesson-plan/lesson-activity/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('edu_lesson_plan_lesson_activities', ['id' => $id]);
    }

    public function test_mutations_rejected_once_plan_is_published(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $lessonId = $this->addLesson($planId, 'One');

        $id = $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', [
            'lesson_plan_lesson_id' => $lessonId,
            'title' => 'Warm-up',
        ])->json('data.id');

        $this->postJson("/v1/edu/lesson-plan/publish/{$planId}")->assertStatus(200);

        $this->postJson('/v1/edu/lesson-plan/lesson-activity/create', [
            'lesson_plan_lesson_id' => $lessonId,
            'title' => 'Practice',
        ])->assertJsonPath('success', false);

        $this->putJson("/v1/edu/lesson-plan/lesson-activity/update/{$id}", ['title' => 'x'])
            ->assertJsonPath('success', false);

        $this->deleteJson("/v1/edu/lesson-plan/lesson-activity/delete/{$id}")
            ->assertJsonPath('success', false);
    }
}
