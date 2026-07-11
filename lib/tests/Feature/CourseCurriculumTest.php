<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class CourseCurriculumTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = $this->makeCourseId();
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'IELTS Foundation',
            'code' => 'CRS_'.strtoupper(uniqid()),
            'duration_minutes' => 90,
            'price_per_lesson' => 250000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeCurriculumId(array $overrides = []): int
    {
        return DB::table('edu_course_curriculums')->insertGetId(array_merge([
            'course_id' => $this->courseId,
            'title' => 'Unit 1',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/course-curriculum/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/course-curriculum/list')->assertJsonPath('code', 403);
    }

    public function test_manager_with_permission_can_access(): void
    {
        $this->actingAsManager(['course_curriculum.list']);

        $this->getJson('/v1/edu/course-curriculum/list')->assertJsonPath('success', true);
    }

    public function test_can_create_curriculum_item(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/edu/course-curriculum/create', [
            'course_id' => $this->courseId,
            'title' => 'Ngữ pháp nền tảng',
            'order' => 1,
            'content' => 'Ôn tập thì hiện tại đơn.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Ngữ pháp nền tảng')
            ->assertJsonPath('data.course_id', $this->courseId);

        $this->assertDatabaseHas('edu_course_curriculums', [
            'course_id' => $this->courseId,
            'title' => 'Ngữ pháp nền tảng',
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/course-curriculum/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['course_id', 'title', 'order']);
    }

    public function test_can_list_and_filter_by_course(): void
    {
        $this->actingAsAdmin();

        $otherCourseId = $this->makeCourseId();
        $this->makeCurriculumId(['title' => 'Unit A']);
        $this->makeCurriculumId(['title' => 'Unit B']);
        $this->makeCurriculumId(['course_id' => $otherCourseId, 'title' => 'Other course unit']);

        $this->getJson('/v1/edu/course-curriculum/list?course_id='.$this->courseId)
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);
    }

    public function test_can_view_detail(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeCurriculumId(['title' => 'Unit A', 'content' => 'Nội dung A']);

        $this->getJson("/v1/edu/course-curriculum/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Unit A')
            ->assertJsonPath('data.content', 'Nội dung A');
    }

    public function test_can_update_curriculum_item(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeCurriculumId(['title' => 'Old title']);

        $this->putJson("/v1/edu/course-curriculum/update/{$id}", ['title' => 'New title', 'order' => 2])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'New title')
            ->assertJsonPath('data.order', 2);

        $this->assertDatabaseHas('edu_course_curriculums', ['id' => $id, 'title' => 'New title', 'order' => 2]);
    }

    public function test_update_cannot_reassign_course(): void
    {
        $this->actingAsAdmin();

        $otherCourseId = $this->makeCourseId();
        $id = $this->makeCurriculumId();

        $this->putJson("/v1/edu/course-curriculum/update/{$id}", ['course_id' => $otherCourseId, 'title' => 'x'])
            ->assertStatus(200);

        $this->assertDatabaseHas('edu_course_curriculums', ['id' => $id, 'course_id' => $this->courseId]);
    }

    public function test_can_delete_curriculum_item(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeCurriculumId();

        $this->deleteJson("/v1/edu/course-curriculum/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('edu_course_curriculums', ['id' => $id]);
    }
}
