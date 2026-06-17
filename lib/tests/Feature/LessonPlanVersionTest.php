<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLessonPlans;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LessonPlanVersionTest extends TestCase
{
    use BuildsLessonPlans;
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLessonPlanContext();
    }

    public function test_version_history_api_lists_and_shows_versions(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $this->addLesson($planId, 'Alphabet');
        $this->postJson("/v1/edu/lesson-plan/publish/{$planId}")->assertStatus(200);

        $list = $this->getJson("/v1/edu/lesson-plan/version/list/{$planId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.version', 1)
            ->assertJsonPath('data.0.lesson_plan_id', $planId);

        $versionId = $list->json('data.0.id');

        $this->getJson("/v1/edu/lesson-plan/version/detail/{$versionId}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $versionId)
            ->assertJsonPath('data.version', 1);
    }
}
