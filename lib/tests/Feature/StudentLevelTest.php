<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class StudentLevelTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $courseId;

    private int $level1;

    private int $level2;

    private int $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId();
        $this->courseId = $this->makeCourseId();
        $this->level1 = $this->makeLevel($this->courseId, 'STARTER', 1);
        $this->level2 = $this->makeLevel($this->courseId, 'MOVER', 2);
        $this->studentId = $this->makeStudentId();
    }

    private function makeBranchId(): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'CN_'.strtoupper(uniqid()),
            'address' => '123 Le Loi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Kids English',
            'code' => 'C_'.strtoupper(uniqid()),
            'duration_minutes' => 60,
            'price_per_lesson' => 100000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeLevel(int $courseId, string $code, int $order): int
    {
        return DB::table('edu_levels')->insertGetId([
            'business_id' => $this->businessId,
            'level_code' => $code.'_'.strtoupper(uniqid()),
            'level_name' => $code,
            'course_id' => $courseId,
            'level_order' => $order,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeStudentId(): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function placement(array $overrides = [])
    {
        return $this->postJson('/v1/edu/student-level/placement', array_merge([
            'student_id' => $this->studentId,
            'course_id' => $this->courseId,
            'level_id' => $this->level1,
            'score' => 25,
        ], $overrides));
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/v1/edu/student-level/placement')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->postJson('/v1/edu/student-level/placement', [])->assertJsonPath('code', 403);
    }

    public function test_placement_assigns_current_level_and_logs_history(): void
    {
        $this->actingAsAdmin();

        $this->placement()
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.level_id', $this->level1)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('edu_student_levels', [
            'student_id' => $this->studentId,
            'level_id' => $this->level1,
        ]);
        $this->assertDatabaseHas('edu_student_level_assessments', [
            'student_id' => $this->studentId,
            'assessment_type' => 'placement_test',
        ]);
        $this->assertDatabaseHas('edu_student_level_histories', [
            'student_id' => $this->studentId,
            'action' => 'placement',
            'to_level_id' => $this->level1,
        ]);
    }

    public function test_placement_rejects_level_outside_course(): void
    {
        $this->actingAsAdmin();

        $otherCourse = $this->makeCourseId();
        $otherLevel = $this->makeLevel($otherCourse, 'OTHER', 1);

        $this->placement(['level_id' => $otherLevel])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_placement_keeps_single_current_level(): void
    {
        $this->actingAsAdmin();

        $this->placement();
        $this->placement(['level_id' => $this->level2]);

        $this->assertSame(1, DB::table('edu_student_levels')->where('student_id', $this->studentId)->count());
        $this->assertDatabaseHas('edu_student_levels', [
            'student_id' => $this->studentId,
            'level_id' => $this->level2,
        ]);
    }

    public function test_promote_moves_to_next_level(): void
    {
        $this->actingAsAdmin();

        $studentLevelId = $this->placement()->json('data.id');

        $this->postJson("/v1/edu/student-level/promote/{$studentLevelId}", [])
            ->assertStatus(200)
            ->assertJsonPath('data.level_id', $this->level2)
            ->assertJsonPath('data.level.level_order', 2);

        $this->assertDatabaseHas('edu_student_level_histories', [
            'student_level_id' => $studentLevelId,
            'action' => 'promote',
            'from_level_id' => $this->level1,
            'to_level_id' => $this->level2,
        ]);
    }

    public function test_promote_fails_when_no_higher_level(): void
    {
        $this->actingAsAdmin();

        $studentLevelId = $this->placement(['level_id' => $this->level2])->json('data.id');

        $this->postJson("/v1/edu/student-level/promote/{$studentLevelId}", [])
            ->assertJsonPath('success', false);
    }

    public function test_adjust_moves_level_with_reason(): void
    {
        $this->actingAsAdmin();

        $studentLevelId = $this->placement(['level_id' => $this->level2])->json('data.id');

        $this->postJson("/v1/edu/student-level/adjust/{$studentLevelId}", [
            'target_level_id' => $this->level1,
            'reason' => 'Mis-placement.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.level_id', $this->level1);

        $this->assertDatabaseHas('edu_student_level_histories', [
            'student_level_id' => $studentLevelId,
            'action' => 'adjust',
            'to_level_id' => $this->level1,
        ]);
    }

    public function test_detail_and_history(): void
    {
        $this->actingAsAdmin();

        $studentLevelId = $this->placement()->json('data.id');

        $this->getJson("/v1/edu/student-level/detail/{$this->studentId}")
            ->assertStatus(200)
            ->assertJsonPath('data.student_level.level_id', $this->level1)
            ->assertJsonPath('data.progress.weights.attendance', 0.2);

        $this->getJson("/v1/edu/student-level/history/{$studentLevelId}")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_detail_fails_when_unassigned(): void
    {
        $this->actingAsAdmin();

        $this->getJson("/v1/edu/student-level/detail/{$this->studentId}")
            ->assertJsonPath('success', false);
    }
}
