<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLessonPlans;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LessonPlanMaterialTest extends TestCase
{
    use BuildsLessonPlans;
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLessonPlanContext();
    }

    public function test_attach_and_detach_material(): void
    {
        $this->actingAsAdmin();

        $planId = $this->createPlan();
        $lessonId = $this->addLesson($planId, 'Alphabet');

        $materialId = $this->postJson("/v1/edu/lesson-plan/lesson/{$lessonId}/material/attach", ['file_id' => 10, 'material_type' => 'pdf'])
            ->assertStatus(200)
            ->assertJsonPath('data.material_type', 'pdf')
            ->json('data.id');

        $this->assertDatabaseHas('edu_lesson_plan_materials', ['id' => $materialId, 'file_id' => 10]);

        $this->deleteJson("/v1/edu/lesson-plan/material/delete/{$materialId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('edu_lesson_plan_materials', ['id' => $materialId]);
    }
}
