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

    private function makeClassIdWithPlan(int $planId): int
    {
        $classId = DB::table('edu_classes')->insertGetId([
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'TKB Class',
            'course_id' => $this->courseId,
            'lesson_plan_id' => $planId,
            'business_id' => $this->businessId,
            'learning_type' => 'scheduled',
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mirrors what ClassService::create() does for a real request: the
        // class's single plan is also its one available start-time option.
        DB::table('edu_class_lesson_plans')->insert([
            'class_room_id' => $classId,
            'lesson_plan_id' => $planId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $classId;
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

    /** A published lesson plan with N template lessons (lesson.md §7). */
    private function makePublishedPlan(int $lessons = 3): int
    {
        $planId = DB::table('edu_lesson_plans')->insertGetId([
            'plan_code' => 'P_'.strtoupper(uniqid()),
            'plan_name' => 'Plan',
            'business_id' => $this->businessId,
            'course_id' => $this->courseId,
            'version' => 1,
            'total_lessons' => $lessons,
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 1; $i <= $lessons; $i++) {
            DB::table('edu_lesson_plan_lessons')->insert([
                'lesson_plan_id' => $planId,
                'business_id' => $this->businessId,
                'lesson_no' => $i,
                'lesson_title' => "Lesson {$i}",
                'objective' => "Objective {$i}",
                'vocabulary' => "Vocab {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $planId;
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

    private function createTimetableSessionId(array $overrides = []): int
    {
        $timetableId = $this->postJson('/v1/edu/timetable/create', $this->payload($overrides))->json('data.id');

        return DB::table('edu_sessions')->where('timetable_id', $timetableId)->orderBy('id')->value('id');
    }

    public function test_change_teacher_updates_session_and_records_history(): void
    {
        $this->actingAsAdmin();
        $sessionId = $this->createTimetableSessionId();
        $newTeacherId = $this->makeTeacherId();

        $this->postJson("/v1/edu/timetable/session/{$sessionId}/change-teacher", [
            'teacher_id' => $newTeacherId,
            'reason' => 'Giáo viên A nghỉ đột xuất.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.teacher_id', $newTeacherId);

        $this->assertDatabaseHas('edu_sessions', ['id' => $sessionId, 'teacher_id' => $newTeacherId]);
        $this->assertDatabaseHas('edu_timetable_changes', [
            'session_id' => $sessionId,
            'change_type' => 'teacher_change',
            'reason' => 'Giáo viên A nghỉ đột xuất.',
        ]);
    }

    public function test_change_room_updates_session_and_records_history(): void
    {
        $this->actingAsAdmin();
        $sessionId = $this->createTimetableSessionId();
        $newRoomId = $this->makeRoomId();

        $this->postJson("/v1/edu/timetable/session/{$sessionId}/change-room", ['room_id' => $newRoomId])
            ->assertStatus(200)
            ->assertJsonPath('data.room_id', $newRoomId);

        $this->assertDatabaseHas('edu_sessions', ['id' => $sessionId, 'room_id' => $newRoomId]);
        $this->assertDatabaseHas('edu_timetable_changes', [
            'session_id' => $sessionId,
            'change_type' => 'room_change',
        ]);
    }

    public function test_change_room_rejects_conflict(): void
    {
        $this->actingAsAdmin();
        $busyRoomId = $this->makeRoomId();

        // A second timetable occupies busyRoomId at the same slot as our target session.
        $this->postJson('/v1/edu/timetable/create', $this->payload([
            'class_room_id' => $this->makeClassId(),
            'room_id' => $busyRoomId,
            'dates' => [['date' => '2026-07-06', 'start_time' => '18:00', 'end_time' => '19:30']],
        ]))->assertJsonPath('success', true);

        $sessionId = $this->createTimetableSessionId();

        $this->postJson("/v1/edu/timetable/session/{$sessionId}/change-room", ['room_id' => $busyRoomId])
            ->assertJsonPath('success', false);
    }

    public function test_reschedule_moves_session_and_records_history(): void
    {
        $this->actingAsAdmin();
        $sessionId = $this->createTimetableSessionId();

        $this->postJson("/v1/edu/timetable/session/{$sessionId}/reschedule", [
            'session_date' => '2026-07-21',
            'start_time' => '20:00',
            'end_time' => '21:30',
            'reason' => 'Trùng lịch phòng.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.session_date', '2026-07-21');

        $this->assertDatabaseHas('edu_sessions', [
            'id' => $sessionId,
            'session_date' => '2026-07-21 00:00:00',
            'start_time' => '20:00:00',
        ]);
        $this->assertDatabaseHas('edu_timetable_changes', [
            'session_id' => $sessionId,
            'change_type' => 'reschedule',
        ]);
    }

    public function test_cancel_session_updates_status_and_records_history(): void
    {
        $this->actingAsAdmin();
        $sessionId = $this->createTimetableSessionId();

        $this->postJson("/v1/edu/timetable/session/{$sessionId}/cancel", ['reason' => 'Nghỉ lễ.'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('edu_sessions', ['id' => $sessionId, 'status' => 'cancelled']);
        $this->assertDatabaseHas('edu_timetable_changes', [
            'session_id' => $sessionId,
            'change_type' => 'cancel',
            'reason' => 'Nghỉ lễ.',
        ]);
    }

    public function test_cancel_session_requires_reason(): void
    {
        $this->actingAsAdmin();
        $sessionId = $this->createTimetableSessionId();

        $this->postJson("/v1/edu/timetable/session/{$sessionId}/cancel", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_session_operations_reject_completed_session(): void
    {
        $this->actingAsAdmin();
        $sessionId = $this->createTimetableSessionId();
        DB::table('edu_sessions')->where('id', $sessionId)->update(['status' => 'completed']);

        $this->postJson("/v1/edu/timetable/session/{$sessionId}/cancel", ['reason' => 'x'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Buổi học đã hoàn thành, không thể sửa.');
    }

    public function test_session_operations_reject_session_without_timetable(): void
    {
        $this->actingAsAdmin();

        $orphanId = DB::table('edu_sessions')->insertGetId([
            'business_id' => $this->businessId,
            'class_id' => $this->classId,
            'session_no' => 1,
            'code' => 'ORPHAN-01',
            'name' => 'Buổi lẻ',
            'session_date' => '2026-07-06',
            'start_time' => '18:00:00',
            'end_time' => '19:30:00',
            'status' => 'upcoming',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson("/v1/edu/timetable/session/{$orphanId}/cancel", ['reason' => 'x'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Buổi học này không thuộc thời khóa biểu nào.');
    }

    // ── Lesson generation (lesson.md §7, now deferred to session-start) ────────

    public function test_create_never_generates_lessons_up_front_even_with_a_plan(): void
    {
        $this->actingAsAdmin();
        $planId = $this->makePublishedPlan(2);
        $classId = $this->makeClassIdWithPlan($planId);

        $this->postJson('/v1/edu/timetable/create', $this->payload([
            'class_room_id' => $classId,
            'dates' => [
                ['date' => '2026-07-06', 'start_time' => '18:00', 'end_time' => '19:30'],
                ['date' => '2026-07-08', 'start_time' => '18:00', 'end_time' => '19:30'],
            ],
        ]))->assertJsonPath('success', true);

        // No Lesson is paired at creation time anymore — only when a session starts
        // and a plan is explicitly chosen (ClassSessionTest covers that flow).
        $this->assertSame(0, DB::table('edu_lessons')->where('class_room_id', $classId)->count());
    }

    public function test_create_does_not_generate_lessons_without_a_plan(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/timetable/create', $this->payload())->assertJsonPath('success', true);

        $this->assertSame(0, DB::table('edu_lessons')->where('class_room_id', $this->classId)->count());
    }

    public function test_starting_session_with_plan_generates_lesson_and_repeats_for_next_session(): void
    {
        $this->actingAsAdmin();
        $planId = $this->makePublishedPlan(2);
        $classId = $this->makeClassIdWithPlan($planId);

        $timetableId = $this->postJson('/v1/edu/timetable/create', $this->payload([
            'class_room_id' => $classId,
            'dates' => [
                ['date' => '2026-07-06', 'start_time' => '18:00', 'end_time' => '19:30'],
                ['date' => '2026-07-08', 'start_time' => '18:00', 'end_time' => '19:30'],
            ],
        ]))->json('data.id');

        $sessionIds = DB::table('edu_sessions')
            ->where('timetable_id', $timetableId)
            ->orderBy('session_no')
            ->pluck('id');

        $this->postJson("/v1/edu/class-session/start/{$sessionIds[0]}", ['lesson_plan_id' => $planId])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $firstLesson = DB::table('edu_lessons')->where('session_id', $sessionIds[0])->first();
        $this->assertNotNull($firstLesson);
        $this->assertSame('Lesson 1', $firstLesson->lesson_title);
        $this->assertSame(1, $firstLesson->lesson_no);

        $this->postJson("/v1/edu/class-session/start/{$sessionIds[1]}", ['lesson_plan_id' => $planId])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $secondLesson = DB::table('edu_lessons')->where('session_id', $sessionIds[1])->first();
        $this->assertSame('Lesson 2', $secondLesson->lesson_title);
        $this->assertSame(2, $secondLesson->lesson_no);
    }

    public function test_starting_session_with_explicit_lesson_uses_that_template_out_of_order(): void
    {
        $this->actingAsAdmin();
        $planId = $this->makePublishedPlan(3);
        $classId = $this->makeClassIdWithPlan($planId);
        $sessionId = $this->createTimetableSessionId(['class_room_id' => $classId]);

        $thirdTemplateId = DB::table('edu_lesson_plan_lessons')
            ->where('lesson_plan_id', $planId)
            ->where('lesson_no', 3)
            ->value('id');

        $this->postJson("/v1/edu/class-session/start/{$sessionId}", [
            'lesson_plan_id' => $planId,
            'lesson_plan_lesson_id' => $thirdTemplateId,
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $lesson = DB::table('edu_lessons')->where('session_id', $sessionId)->first();
        $this->assertNotNull($lesson);
        $this->assertSame('Lesson 3', $lesson->lesson_title);
        $this->assertSame($thirdTemplateId, $lesson->lesson_plan_lesson_id);
    }

    public function test_starting_session_rejects_lesson_already_used(): void
    {
        $this->actingAsAdmin();
        $planId = $this->makePublishedPlan(2);
        $classId = $this->makeClassIdWithPlan($planId);

        $firstTemplateId = DB::table('edu_lesson_plan_lessons')
            ->where('lesson_plan_id', $planId)
            ->where('lesson_no', 1)
            ->value('id');

        $firstSessionId = $this->createTimetableSessionId(['class_room_id' => $classId]);
        $this->postJson("/v1/edu/class-session/start/{$firstSessionId}", [
            'lesson_plan_id' => $planId,
            'lesson_plan_lesson_id' => $firstTemplateId,
        ])->assertJsonPath('success', true);

        $secondSessionId = $this->createTimetableSessionId(['class_room_id' => $classId]);
        $this->postJson("/v1/edu/class-session/start/{$secondSessionId}", [
            'lesson_plan_id' => $planId,
            'lesson_plan_lesson_id' => $firstTemplateId,
        ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Bài học này đã được sử dụng cho một buổi học khác.');
    }

    public function test_starting_session_rejects_lesson_from_another_plan(): void
    {
        $this->actingAsAdmin();
        $planId = $this->makePublishedPlan(2);
        $otherPlanId = $this->makePublishedPlan(1);
        $classId = $this->makeClassIdWithPlan($planId);
        $sessionId = $this->createTimetableSessionId(['class_room_id' => $classId]);

        $otherTemplateId = DB::table('edu_lesson_plan_lessons')
            ->where('lesson_plan_id', $otherPlanId)
            ->value('id');

        $this->postJson("/v1/edu/class-session/start/{$sessionId}", [
            'lesson_plan_id' => $planId,
            'lesson_plan_lesson_id' => $otherTemplateId,
        ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Bài học này không thuộc giáo án đã chọn.');
    }

    public function test_starting_session_without_plan_leaves_it_bare(): void
    {
        $this->actingAsAdmin();
        $planId = $this->makePublishedPlan(2);
        $classId = $this->makeClassIdWithPlan($planId);
        $sessionId = $this->createTimetableSessionId(['class_room_id' => $classId]);

        $this->postJson("/v1/edu/class-session/start/{$sessionId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSame(0, DB::table('edu_lessons')->where('session_id', $sessionId)->count());
    }

    public function test_starting_session_rejects_plan_not_linked_to_class(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $sessionId = $this->createTimetableSessionId(['class_room_id' => $classId]);
        $unlinkedPlanId = $this->makePublishedPlan(1);

        $this->postJson("/v1/edu/class-session/start/{$sessionId}", ['lesson_plan_id' => $unlinkedPlanId])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Giáo án này chưa được gắn với lớp học.');
    }
}
