<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $courseId;

    private int $classId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId();
        $this->courseId = $this->makeCourseId();
        $this->classId = $this->makeClassId();
    }

    private function makeBranchId(): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'BR_'.strtoupper(uniqid()),
            'address' => '1 Test St',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function makeClassId(): int
    {
        return DB::table('edu_classes')->insertGetId([
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'TKB Class',
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'learning_type' => 'scheduled',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeRoomId(int $capacity = 30): int
    {
        return DB::table('edu_rooms')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'room_code' => 'R_'.strtoupper(uniqid()),
            'room_name' => 'Room '.uniqid(),
            'room_type' => 'classroom',
            'capacity' => $capacity,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeTeacherId(): int
    {
        return DB::table('hr_teachers')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Teacher '.uniqid(),
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

    private function linkStudent(int $studentId, ?int $classId = null): void
    {
        DB::table('edu_class_students')->insert([
            'class_id' => $classId ?? $this->classId,
            'student_id' => $studentId,
            'status' => 'active',
            'enrolled_at' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'TKB Test',
            'course_id' => $this->courseId,
            'class_room_id' => $this->classId,
            'teacher_id' => $this->makeTeacherId(),
            'room_id' => $this->makeRoomId(),
            'start_date' => '2026-07-01',
            'end_date' => '2026-08-31',
            'schedule_pattern' => 'specific_dates',
            'dates' => [
                ['date' => '2026-07-06', 'start_time' => '18:00', 'end_time' => '19:30'],
                ['date' => '2026-07-08', 'start_time' => '18:00', 'end_time' => '19:30'],
            ],
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/timetable/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/timetable/list')->assertJsonPath('code', 403);
    }

    public function test_create_generates_sessions(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/timetable/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.timetable_code', 'TKB000001')
            ->assertJsonPath('data.total_sessions', 2)
            ->assertJsonCount(2, 'data.sessions');
    }

    public function test_create_fixed_weekly_generates_from_rules(): void
    {
        $this->actingAsAdmin();

        // 2026-07-06 is a Monday; one week range with Mon + Wed rules => 2 sessions.
        $id = $this->postJson('/v1/edu/timetable/create', $this->payload([
            'schedule_pattern' => 'fixed_weekly',
            'start_date' => '2026-07-06',
            'end_date' => '2026-07-10',
            'dates' => null,
            'rules' => [
                ['day_of_week' => 1, 'start_time' => '18:00', 'end_time' => '19:30'],
                ['day_of_week' => 3, 'start_time' => '18:00', 'end_time' => '19:30'],
            ],
        ]))
            ->assertStatus(200)
            ->assertJsonPath('data.total_sessions', 2)
            ->json('data.id');

        $this->getJson("/v1/edu/timetable/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.rules')
            ->assertJsonCount(2, 'data.sessions');
    }

    public function test_room_conflict_is_rejected(): void
    {
        $this->actingAsAdmin();
        $roomId = $this->makeRoomId();

        $this->postJson('/v1/edu/timetable/create', $this->payload([
            'room_id' => $roomId,
            'dates' => [['date' => '2026-07-06', 'start_time' => '18:00', 'end_time' => '19:30']],
        ]))->assertJsonPath('success', true);

        // Same room, same date, overlapping time => BR-01.
        $this->postJson('/v1/edu/timetable/create', $this->payload([
            'room_id' => $roomId,
            'class_room_id' => $this->makeClassId(),
            'dates' => [['date' => '2026-07-06', 'start_time' => '19:00', 'end_time' => '20:30']],
        ]))
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Phòng học đã có lịch trùng vào 2026-07-06 19:00:00.');
    }

    public function test_teacher_conflict_is_rejected(): void
    {
        $this->actingAsAdmin();
        $teacherId = $this->makeTeacherId();

        $this->postJson('/v1/edu/timetable/create', $this->payload([
            'teacher_id' => $teacherId,
            'room_id' => $this->makeRoomId(),
            'dates' => [['date' => '2026-07-06', 'start_time' => '18:00', 'end_time' => '19:30']],
        ]))->assertJsonPath('success', true);

        $this->postJson('/v1/edu/timetable/create', $this->payload([
            'teacher_id' => $teacherId,
            'room_id' => $this->makeRoomId(),
            'dates' => [['date' => '2026-07-06', 'start_time' => '18:00', 'end_time' => '19:30']],
        ]))
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Giáo viên đã có lịch trùng vào 2026-07-06 18:00:00.');
    }

    public function test_capacity_is_enforced(): void
    {
        $this->actingAsAdmin();

        $this->linkStudent($this->makeStudentId());
        $this->linkStudent($this->makeStudentId());

        // Room capacity 1 < 2 active students => BR-03.
        $this->postJson('/v1/edu/timetable/create', $this->payload([
            'room_id' => $this->makeRoomId(1),
            'dates' => [['date' => '2026-07-06', 'start_time' => '18:00', 'end_time' => '19:30']],
        ]))
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Số học viên vượt quá sức chứa phòng học.');
    }

    public function test_list_update_delete(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/timetable/create', $this->payload())->json('data.id');

        $this->getJson('/v1/edu/timetable/list?class_room_id='.$this->classId)
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);

        $this->putJson("/v1/edu/timetable/update/{$id}", ['status' => 'active', 'name' => 'TKB Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.name', 'TKB Updated');

        $this->deleteJson("/v1/edu/timetable/delete/{$id}")->assertStatus(200);
        $this->assertSoftDeleted('edu_timetables', ['id' => $id]);
    }

    public function test_calendar_and_object_schedules(): void
    {
        $this->actingAsAdmin();

        $teacherId = $this->makeTeacherId();
        $roomId = $this->makeRoomId();
        $studentId = $this->makeStudentId();
        $this->linkStudent($studentId);

        $this->postJson('/v1/edu/timetable/create', $this->payload([
            'teacher_id' => $teacherId,
            'room_id' => $roomId,
            'dates' => [
                ['date' => '2026-07-06', 'start_time' => '18:00', 'end_time' => '19:30'],
                ['date' => '2026-07-08', 'start_time' => '18:00', 'end_time' => '19:30'],
            ],
        ]))->assertJsonPath('success', true);

        $this->getJson('/v1/edu/timetable/calendar?date_from=2026-07-01&date_to=2026-07-31')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Range filter excludes out-of-window sessions.
        $this->getJson('/v1/edu/timetable/calendar?date_from=2026-07-07&date_to=2026-07-31')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->getJson("/v1/edu/timetable/teacher/{$teacherId}/schedule")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.teacher_id', $teacherId);

        $this->getJson("/v1/edu/timetable/room/{$roomId}/schedule")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->getJson("/v1/edu/timetable/student/{$studentId}/schedule")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
