<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLessonPlans;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LessonPlanLessonTest extends TestCase
{
    use BuildsLessonPlans;
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLessonPlanContext();
    }

    public function test_add_lessons_keeps_continuity_and_recomputes_total(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();

        $this->postJson("/v1/edu/lesson-plan/lesson/create/{$planId}", ['lesson_title' => 'Alphabet'])
            ->assertStatus(200)
            ->assertJsonPath('data.lesson_no', 1);
        $this->postJson("/v1/edu/lesson-plan/lesson/create/{$planId}", ['lesson_title' => 'Numbers'])
            ->assertStatus(200)
            ->assertJsonPath('data.lesson_no', 2);

        $this->assertDatabaseHas('edu_lesson_plans', ['id' => $planId, 'total_lessons' => 2]);
    }

    public function test_add_lesson_with_non_contiguous_no_is_rejected(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $this->addLesson($planId, 'Alphabet');

        // Next must be 2; sending 5 violates BR003.
        $this->postJson("/v1/edu/lesson-plan/lesson/create/{$planId}", ['lesson_title' => 'Skip', 'lesson_no' => 5])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_delete_lesson_resequences(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $l1 = $this->addLesson($planId, 'One');
        $l2 = $this->addLesson($planId, 'Two');
        $l3 = $this->addLesson($planId, 'Three');

        $this->deleteJson("/v1/edu/lesson-plan/lesson/delete/{$l2}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Remaining lessons re-sequenced to 1, 2.
        $this->assertDatabaseHas('edu_lesson_plan_lessons', ['id' => $l1, 'lesson_no' => 1]);
        $this->assertDatabaseHas('edu_lesson_plan_lessons', ['id' => $l3, 'lesson_no' => 2]);
        $this->assertDatabaseHas('edu_lesson_plans', ['id' => $planId, 'total_lessons' => 2]);
    }

    public function test_update_lesson_changes_content(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $lessonId = $this->addLesson($planId, 'One');

        $this->putJson("/v1/edu/lesson-plan/lesson/update/{$lessonId}", ['lesson_title' => 'One Updated'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lesson_title', 'One Updated');

        $this->assertDatabaseHas('edu_lesson_plan_lessons', ['id' => $lessonId, 'lesson_title' => 'One Updated']);
    }

    public function test_reorder_lessons(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $l1 = $this->addLesson($planId, 'One');
        $l2 = $this->addLesson($planId, 'Two');
        $l3 = $this->addLesson($planId, 'Three');

        $this->postJson("/v1/edu/lesson-plan/lesson/reorder/{$planId}", ['lesson_ids' => [$l3, $l1, $l2]])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('edu_lesson_plan_lessons', ['id' => $l3, 'lesson_no' => 1]);
        $this->assertDatabaseHas('edu_lesson_plan_lessons', ['id' => $l1, 'lesson_no' => 2]);
        $this->assertDatabaseHas('edu_lesson_plan_lessons', ['id' => $l2, 'lesson_no' => 3]);
    }
}
