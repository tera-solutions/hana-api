<?php

namespace Tests\Concerns;

use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Shared scaffolding for lesson-plan feature tests: permission seeding plus
 * helpers to build a course, class and draft plans with lessons.
 *
 * Expects the using class to also use {@see SeedsAuthContext} (for makeBusinessId)
 * and to authenticate via actingAsAdmin/actingAsManager.
 */
trait BuildsLessonPlans
{
    protected int $businessId;

    protected int $courseId;

    protected function seedLessonPlanContext(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = $this->makeCourseId();
    }

    protected function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Course '.uniqid(),
            'code' => 'C_'.strtoupper(uniqid()),
            'duration_minutes' => 90,
            'price_per_lesson' => 100000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeClassId(): int
    {
        return DB::table('edu_classes')->insertGetId([
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'name' => 'Class '.uniqid(),
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'plan_code' => 'KIDS_STARTER_V1',
            'plan_name' => 'Kids Starter',
            'course_id' => $this->courseId,
            'description' => 'Giáo án cấp độ Starter.',
        ], $overrides);
    }

    protected function createPlan(array $overrides = []): int
    {
        return $this->postJson('/v1/edu/lesson-plan/create', $this->payload($overrides))->json('data.id');
    }

    protected function addLesson(int $planId, string $title = 'Lesson'): int
    {
        return $this->postJson("/v1/edu/lesson-plan/lesson/create/{$planId}", ['lesson_title' => $title])->json('data.id');
    }
}
