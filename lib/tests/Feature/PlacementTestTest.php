<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class PlacementTestTest extends TestCase
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

    private function makeStudentId(): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/placement-test/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([], $this->businessId);

        $this->getJson('/v1/edu/placement-test/list')->assertJsonPath('code', 403);
    }

    public function test_create_generates_code_and_defaults_to_draft(): void
    {
        $this->actingAsManager(['placement_test.create', 'placement_test.list'], $this->businessId);

        $response = $this->postJson('/v1/edu/placement-test/create', [
            'title' => 'Kiểm tra đầu vào A1',
            'cefr_level' => 'A1',
            'skills' => ['listening', 'grammar'],
            'question_count' => 50,
            'duration_minutes' => 60,
        ])->assertStatus(200);

        $response->assertJsonPath('data.title', 'Kiểm tra đầu vào A1')
            ->assertJsonPath('data.status', 'draft');
        $this->assertNotEmpty($response->json('data.test_code'));

        $this->getJson('/v1/edu/placement-test/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_create_requires_title(): void
    {
        $this->actingAsManager(['placement_test.create'], $this->businessId);

        $this->postJson('/v1/edu/placement-test/create', [])
            ->assertStatus(422);
    }

    public function test_publish_flips_status(): void
    {
        $this->actingAsManager(['placement_test.create', 'placement_test.update'], $this->businessId);

        $id = $this->postJson('/v1/edu/placement-test/create', ['title' => 'Test A'])
            ->json('data.id');

        $this->postJson("/v1/edu/placement-test/publish/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'published');
    }

    public function test_record_result_then_list_results(): void
    {
        $this->actingAsManager(
            ['placement_test.create', 'placement_test.view', 'placement_test.update'],
            $this->businessId,
        );

        $id = $this->postJson('/v1/edu/placement-test/create', ['title' => 'Test A'])
            ->json('data.id');
        $studentId = $this->makeStudentId();

        $this->postJson("/v1/edu/placement-test/results/{$id}", [
            'student_id' => $studentId,
            'score' => 8.5,
            'cefr_result' => 'A2',
            'completion_rate' => 100,
            'status' => 'completed',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.score', '8.50')
            ->assertJsonPath('data.cefr_result', 'A2');

        $this->getJson("/v1/edu/placement-test/results/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);

        $this->getJson("/v1/edu/placement-test/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.stats.attempts', 1)
            ->assertJsonPath('data.stats.avg_score', 8.5);
    }

    public function test_record_result_requires_student(): void
    {
        $this->actingAsManager(['placement_test.create', 'placement_test.update'], $this->businessId);

        $id = $this->postJson('/v1/edu/placement-test/create', ['title' => 'Test A'])
            ->json('data.id');

        $this->postJson("/v1/edu/placement-test/results/{$id}", ['score' => 5])
            ->assertStatus(422);
    }

    public function test_delete_removes_test(): void
    {
        $this->actingAsManager(
            ['placement_test.create', 'placement_test.delete', 'placement_test.list'],
            $this->businessId,
        );

        $id = $this->postJson('/v1/edu/placement-test/create', ['title' => 'Test A'])
            ->json('data.id');

        $this->deleteJson("/v1/edu/placement-test/delete/{$id}")->assertStatus(200);

        $this->getJson('/v1/edu/placement-test/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }

    public function test_tests_are_scoped_to_business(): void
    {
        $this->actingAsManager(['placement_test.create'], $this->businessId);
        $this->postJson('/v1/edu/placement-test/create', ['title' => 'Test A'])->assertStatus(200);

        $otherBusinessId = $this->makeBusinessId();
        $this->actingAsManager(['placement_test.list'], $otherBusinessId);

        $this->getJson('/v1/edu/placement-test/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }
}
