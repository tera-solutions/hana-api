<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class CourseTest extends TestCase
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
            'name' => 'IELTS Foundation',
            'code' => 'IELTS_6_5',
            'duration_minutes' => 90,
            'price_per_lesson' => 250000,
            'description' => 'Beginner IELTS course.',
            'business_id' => $this->businessId,
        ], $overrides);
    }

    private function makeClassFor(int $courseId): void
    {
        DB::table('edu_classes')->insert([
            'course_id' => $courseId,
            'business_id' => $this->businessId,
            'name' => 'Class A',
            'start_date' => now()->toDateString(),
            'status' => 'running',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/course/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/course/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_course(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/edu/course/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'IELTS_6_5')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('edu_courses', ['code' => 'IELTS_6_5', 'is_active' => true]);
    }

    public function test_create_validates_required_and_code_format(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/course/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'duration_minutes', 'price_per_lesson']);

        $this->postJson('/v1/edu/course/create', $this->payload(['code' => 'bad code!']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_create_rejects_duplicate_code(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/course/create', $this->payload())->assertStatus(200);

        $this->postJson('/v1/edu/course/create', $this->payload(['name' => 'Other']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_can_list_and_search(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/course/create', $this->payload())->assertStatus(200);
        $this->postJson('/v1/edu/course/create', $this->payload(['code' => 'TOEIC_1', 'name' => 'TOEIC Intro']))->assertStatus(200);

        $this->getJson('/v1/edu/course/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/edu/course/list?search=TOEIC')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.code', 'TOEIC_1');
    }

    public function test_detail_returns_statistics(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/course/create', $this->payload())->json('data.id');
        $this->makeClassFor($id);

        $this->getJson("/v1/edu/course/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.course.id', $id)
            ->assertJsonPath('data.statistics.operational.total_classes', 1)
            ->assertJsonPath('data.statistics.operational.active_classes', 1)
            ->assertJsonStructure([
                'data' => ['statistics' => [
                    'operational' => ['total_classes', 'active_classes', 'total_students', 'studying_students', 'reserved_students', 'completed_students'],
                    'financial' => ['revenue_sales', 'recognized_revenue', 'refunds', 'debt', 'balance'],
                    'rating' => ['average_rating', 'total_reviews', 'satisfaction_rate'],
                ]],
            ]);
    }

    public function test_report_endpoints(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/course/create', $this->payload())->json('data.id');

        $this->getJson("/v1/edu/course/statistics/{$id}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_classes', 'active_classes']]);
        $this->getJson("/v1/edu/course/financial-summary/{$id}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['revenue_sales', 'balance']]);
        $this->getJson("/v1/edu/course/rating-summary/{$id}")
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['average_rating', 'total_reviews']]);
    }

    public function test_can_update_course(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/course/create', $this->payload())->json('data.id');

        $this->putJson("/v1/edu/course/update/{$id}", ['name' => 'IELTS Advanced', 'price_per_lesson' => 300000])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'IELTS Advanced');

        $this->assertDatabaseHas('edu_courses', ['id' => $id, 'name' => 'IELTS Advanced']);
    }

    public function test_code_immutable_once_classes_exist(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/course/create', $this->payload())->json('data.id');
        $this->makeClassFor($id);

        $this->putJson("/v1/edu/course/update/{$id}", ['code' => 'NEW_CODE'])
            ->assertStatus(200);

        // Code unchanged because the course already has classes.
        $this->assertDatabaseHas('edu_courses', ['id' => $id, 'code' => 'IELTS_6_5']);
    }

    public function test_suspend_and_restore_lifecycle(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/course/create', $this->payload())->json('data.id');

        $this->postJson("/v1/edu/course/suspend/{$id}", ['reason' => 'Tạm dừng tuyển sinh'])
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('edu_course_histories', ['course_id' => $id, 'action' => 'suspended']);

        // Suspending again is rejected.
        $this->postJson("/v1/edu/course/suspend/{$id}", ['reason' => 'x'])
            ->assertJsonPath('success', false);

        $this->postJson("/v1/edu/course/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('edu_course_histories', ['course_id' => $id, 'action' => 'restored']);
    }

    public function test_suspend_requires_reason(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/course/create', $this->payload())->json('data.id');

        $this->postJson("/v1/edu/course/suspend/{$id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/course/create', $this->payload())->json('data.id');

        $this->assertDatabaseHas('edu_courses', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
