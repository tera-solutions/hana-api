<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId($this->businessId);
    }

    private function makeBranchId(int $businessId): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'BR_'.strtoupper(uniqid()),
            'address' => '123 Test St',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branchId,
            'room_code' => 'A101',
            'room_name' => 'Phòng A101',
            'floor' => '1',
            'capacity' => 25,
            'room_type' => 'classroom',
            'description' => 'Phòng học chính.',
        ], $overrides);
    }

    private function makeCourseId(): int
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

    private function makeClass(int $roomId, string $status = 'active'): int
    {
        return DB::table('edu_classes')->insertGetId([
            'course_id' => $this->makeCourseId(),
            'business_id' => $this->businessId,
            'room_id' => $roomId,
            'name' => 'Class '.uniqid(),
            'start_date' => now()->toDateString(),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function enrollStudents(int $classId, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $studentId = DB::table('edu_students')->insertGetId([
                'business_id' => $this->businessId,
                'branch_id' => $this->branchId,
                'code' => 'S_'.strtoupper(uniqid()),
                'name' => 'Student '.uniqid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('edu_class_students')->insert([
                'class_id' => $classId,
                'student_id' => $studentId,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function makeSession(int $roomId, string $status, string $date, string $start = '08:00:00', string $end = '10:00:00'): int
    {
        $classId = $this->makeClass($roomId);

        return DB::table('edu_sessions')->insertGetId([
            'class_id' => $classId,
            'room_id' => $roomId,
            'session_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeLesson(int $roomId, string $status, string $date, string $start = '08:00:00', string $end = '10:00:00'): int
    {
        $classId = $this->makeClass($roomId);

        return DB::table('edu_lessons')->insertGetId([
            'class_room_id' => $classId,
            'room_id' => $roomId,
            'lesson_no' => 1,
            'lesson_title' => 'Lesson '.uniqid(),
            'lesson_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/room/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/room/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_room_with_default_active_status(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/room/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.room_code', 'A101')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('edu_rooms', ['room_code' => 'A101', 'status' => 'active']);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/room/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id', 'room_code', 'room_name', 'capacity', 'room_type']);

        $this->postJson('/v1/edu/room/create', $this->payload(['capacity' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('capacity');

        $this->postJson('/v1/edu/room/create', $this->payload(['room_type' => 'invalid']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('room_type');
    }

    public function test_code_unique_within_branch_but_allowed_across_branches(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/room/create', $this->payload())->assertStatus(200);

        // Same code, same branch -> rejected.
        $this->postJson('/v1/edu/room/create', $this->payload(['room_name' => 'Other']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('room_code');

        // Same code, different branch -> allowed.
        $otherBranch = $this->makeBranchId($this->businessId);
        $this->postJson('/v1/edu/room/create', $this->payload(['branch_id' => $otherBranch]))
            ->assertStatus(200);
    }

    public function test_list_filters_and_search(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/room/create', $this->payload())->assertStatus(200);
        $this->postJson('/v1/edu/room/create', $this->payload(['room_code' => 'LAB1', 'room_name' => 'Lab 1', 'room_type' => 'computer_room']))->assertStatus(200);

        $this->getJson('/v1/edu/room/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/edu/room/list?room_type=computer_room')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.room_code', 'LAB1');

        $this->getJson('/v1/edu/room/list?search=A101')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_detail_returns_statistics_and_classes_in_use(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');
        $this->makeClass($id, 'active');

        $this->getJson("/v1/edu/room/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.room.id', $id)
            ->assertJsonPath('data.statistics.active_classes', 1)
            ->assertJsonStructure([
                'data' => [
                    'room',
                    'statistics' => ['total_classes', 'active_classes', 'total_sessions', 'completed_sessions', 'last_used_at'],
                    'classes_in_use',
                ],
            ]);
    }

    public function test_can_update_room(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');

        $this->putJson("/v1/edu/room/update/{$id}", ['room_name' => 'Phòng A101 mới', 'capacity' => 30])
            ->assertStatus(200)
            ->assertJsonPath('data.room_name', 'Phòng A101 mới')
            ->assertJsonPath('data.capacity', 30);

        $this->assertDatabaseHas('edu_rooms', ['id' => $id, 'room_name' => 'Phòng A101 mới', 'capacity' => 30]);
    }

    public function test_code_immutable_once_classes_exist(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');
        $this->makeClass($id);

        $this->putJson("/v1/edu/room/update/{$id}", ['room_code' => 'NEW'])
            ->assertStatus(200);

        $this->assertDatabaseHas('edu_rooms', ['id' => $id, 'room_code' => 'A101']);
    }

    public function test_capacity_cannot_drop_below_active_class_enrollment(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');
        $classId = $this->makeClass($id, 'active');
        $this->enrollStudents($classId, 20);

        $this->putJson("/v1/edu/room/update/{$id}", ['capacity' => 15])
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        // 20 is allowed.
        $this->putJson("/v1/edu/room/update/{$id}", ['capacity' => 20])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_suspend_blocked_by_ongoing_session(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');
        $this->makeSession($id, 'ongoing', now()->toDateString());

        $this->postJson("/v1/edu/room/suspend/{$id}", ['reason' => 'Bảo trì'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('edu_rooms', ['id' => $id, 'status' => 'active']);
    }

    public function test_suspend_warns_on_future_sessions_unless_forced(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');
        $this->makeSession($id, 'upcoming', now()->addWeek()->toDateString());

        // Without force -> warning, not suspended.
        $this->postJson("/v1/edu/room/suspend/{$id}", ['reason' => 'Bảo trì'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        // With force -> suspended.
        $this->postJson("/v1/edu/room/suspend/{$id}", ['reason' => 'Bảo trì', 'force' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');
    }

    public function test_suspend_and_restore_lifecycle(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');

        $this->postJson("/v1/edu/room/suspend/{$id}", ['reason' => 'Bảo trì'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('edu_room_histories', ['room_id' => $id, 'action' => 'suspended']);

        $this->postJson("/v1/edu/room/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('edu_room_histories', ['room_id' => $id, 'action' => 'restored']);
    }

    public function test_suspend_requires_reason(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');

        $this->postJson("/v1/edu/room/suspend/{$id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_schedule_detects_overlap(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');
        $this->makeSession($id, 'upcoming', '2026-07-01', '08:00:00', '10:00:00');

        // Overlapping slot -> conflict.
        $this->getJson("/v1/edu/room/schedule/{$id}?lesson_date=2026-07-01&start_time=08:30&end_time=10:30")
            ->assertStatus(200)
            ->assertJsonPath('data.has_conflict', true);

        // Non-overlapping slot -> no conflict.
        $this->getJson("/v1/edu/room/schedule/{$id}?lesson_date=2026-07-01&start_time=10:00&end_time=11:00")
            ->assertStatus(200)
            ->assertJsonPath('data.has_conflict', false);
    }

    public function test_schedule_detects_overlap_with_lessons(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');
        $this->makeLesson($id, 'scheduled', '2026-07-01', '08:00:00', '10:00:00');

        $this->getJson("/v1/edu/room/schedule/{$id}?lesson_date=2026-07-01&start_time=08:30&end_time=10:30")
            ->assertStatus(200)
            ->assertJsonPath('data.has_conflict', true)
            ->assertJsonPath('data.conflicts.0.source', 'lesson');
    }

    public function test_suspend_blocked_by_ongoing_lesson(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');
        $this->makeLesson($id, 'in_progress', now()->toDateString());

        $this->postJson("/v1/edu/room/suspend/{$id}", ['reason' => 'Bảo trì'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('edu_rooms', ['id' => $id, 'status' => 'active']);
    }

    public function test_detail_statistics_include_lessons(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');
        $this->makeLesson($id, 'completed', '2026-01-01');

        $this->getJson("/v1/edu/room/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.statistics.total_lessons', 1)
            ->assertJsonPath('data.statistics.completed_lessons', 1)
            ->assertJsonPath('data.statistics.last_used_at', '2026-01-01');
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/room/create', $this->payload())->json('data.id');

        $this->assertDatabaseHas('edu_rooms', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
