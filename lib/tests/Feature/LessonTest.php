<?php

namespace Tests\Feature;

use App\Modules\Education\Lesson\Services\LessonService;
use Database\Seeders\LessonPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    private int $courseId;

    private int $teacherId;

    private int $roomId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LessonPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = $this->makeBranchId();
        $this->courseId = $this->makeCourseId();
        $this->teacherId = $this->makeTeacherId();
        $this->roomId = $this->makeRoomId();
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

    private function makeRoomId(): int
    {
        return DB::table('edu_rooms')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'room_code' => 'R_'.strtoupper(uniqid()),
            'room_name' => 'Room '.uniqid(),
            'capacity' => 20,
            'room_type' => 'classroom',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassId(?int $planId = null): int
    {
        return DB::table('edu_classes')->insertGetId([
            'course_id' => $this->courseId,
            'lesson_plan_id' => $planId,
            'business_id' => $this->businessId,
            'room_id' => $this->roomId,
            'teacher_id' => $this->teacherId,
            'name' => 'Class '.uniqid(),
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeSchedule(int $classId, int $weekday, string $start = '08:00:00', string $end = '10:00:00'): void
    {
        DB::table('edu_class_schedules')->insert([
            'class_id' => $classId,
            'weekday' => $weekday,
            'start_time' => $start,
            'end_time' => $end,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makePublishedPlan(int $lessons = 3): int
    {
        $planId = DB::table('edu_lesson_plans')->insertGetId([
            'plan_code' => 'P_'.strtoupper(uniqid()),
            'plan_name' => 'Plan',
            'course_id' => $this->courseId,
            'version' => 1,
            'total_lessons' => $lessons,
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 1; $i <= $lessons; $i++) {
            $lessonId = DB::table('edu_lesson_plan_lessons')->insertGetId([
                'lesson_plan_id' => $planId,
                'lesson_no' => $i,
                'lesson_title' => "Lesson {$i}",
                'objective' => "Objective {$i}",
                'vocabulary' => "Vocab {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('edu_lesson_plan_lesson_activities')->insert([
                'lesson_plan_lesson_id' => $lessonId,
                'sort_order' => 1,
                'title' => "Warm-up {$i}",
                'duration' => 5,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $planId;
    }

    /** Seed a lesson directly (lessons are generated from plans, not created via API). */
    private function createLesson(array $overrides = []): int
    {
        $classId = $overrides['class_room_id'] ?? $this->makeClassId();
        $nextNo = (int) DB::table('edu_lessons')->where('class_room_id', $classId)->max('lesson_no') + 1;

        return DB::table('edu_lessons')->insertGetId(array_merge([
            'class_room_id' => $classId,
            'lesson_no' => $nextNo,
            'lesson_title' => 'My Family',
            'lesson_date' => now()->addWeek()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'room_id' => $this->roomId,
            'teacher_id' => $this->teacherId,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/lesson/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/lesson/list')->assertJsonPath('code', 403);
    }

    public function test_generate_lessons_from_plan_snapshots_content(): void
    {
        $this->actingAsAdmin();

        $planId = $this->makePublishedPlan(3);
        $classId = $this->makeClassId($planId);
        $this->makeSchedule($classId, 1); // Monday
        $this->makeSchedule($classId, 3); // Wednesday

        $this->postJson("/v1/edu/lesson/generate/{$classId}", ['from_date' => '2026-07-01'])
            ->assertStatus(200)
            ->assertJsonPath('data.created', 3)
            ->assertJsonPath('data.skipped', 0);

        $this->assertSame(3, DB::table('edu_lessons')->where('class_room_id', $classId)->count());

        $first = DB::table('edu_lessons')->where('class_room_id', $classId)->orderBy('lesson_no')->first();
        $this->assertSame('Lesson 1', $first->lesson_title);
        $this->assertSame('Objective 1', $first->objective);
        $this->assertSame('scheduled', $first->status);
        $this->assertDatabaseHas('edu_lesson_activities', ['lesson_id' => $first->id, 'title' => 'Warm-up 1', 'status' => 'pending']);

        // Idempotent.
        $this->postJson("/v1/edu/lesson/generate/{$classId}", ['from_date' => '2026-07-01'])
            ->assertStatus(200)
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.skipped', 3);
    }

    public function test_generate_requires_plan_and_schedule(): void
    {
        $this->actingAsAdmin();

        // No lesson plan on the class.
        $classId = $this->makeClassId();
        $this->postJson("/v1/edu/lesson/generate/{$classId}", ['from_date' => '2026-07-01'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        // Plan but no schedule.
        $planId = $this->makePublishedPlan(2);
        $classId2 = $this->makeClassId($planId);
        $this->postJson("/v1/edu/lesson/generate/{$classId2}", ['from_date' => '2026-07-01'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_update_changes_teacher_and_logs_audit(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();
        $newTeacher = $this->makeTeacherId();

        $this->putJson("/v1/edu/lesson/update/{$id}", ['teacher_id' => $newTeacher, 'lesson_note' => 'Tốt'])
            ->assertStatus(200)
            ->assertJsonPath('data.teacher_id', $newTeacher)
            ->assertJsonPath('data.lesson_note', 'Tốt');

        $this->assertDatabaseHas('edu_lesson_histories', ['lesson_id' => $id, 'action' => 'change_teacher']);
    }

    public function test_update_changes_status_and_logs_audit(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();

        $this->putJson("/v1/edu/lesson/update/{$id}", ['status' => 'confirmed'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('edu_lesson_histories', ['lesson_id' => $id, 'action' => 'change_status']);
    }

    public function test_update_rejects_status_outside_plain_progression(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();

        $this->putJson("/v1/edu/lesson/update/{$id}", ['status' => 'cancelled'])
            ->assertStatus(422);
    }

    public function test_completed_lesson_cannot_be_updated(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();
        DB::table('edu_lessons')->where('id', $id)->update(['status' => 'completed']);

        $this->putJson("/v1/edu/lesson/update/{$id}", ['lesson_note' => 'x'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_reschedule_rejects_past_date(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();

        $this->postJson("/v1/edu/lesson/reschedule/{$id}", [
            'lesson_date' => now()->subDay()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ])->assertStatus(200)->assertJsonPath('success', false);
    }

    public function test_reschedule_rejects_teacher_conflict(): void
    {
        $this->actingAsAdmin();

        $classId = $this->makeClassId();
        $date = now()->addWeek()->toDateString();

        $this->createLesson(['class_room_id' => $classId, 'lesson_date' => $date, 'start_time' => '08:00', 'end_time' => '10:00']);
        $id2 = $this->createLesson(['class_room_id' => $classId, 'lesson_title' => 'B', 'lesson_date' => $date, 'start_time' => '14:00', 'end_time' => '16:00']);

        // Move lesson B onto lesson A's slot (same teacher) → conflict.
        $this->postJson("/v1/edu/lesson/reschedule/{$id2}", [
            'lesson_date' => $date,
            'start_time' => '08:30',
            'end_time' => '09:30',
        ])->assertStatus(200)->assertJsonPath('success', false);
    }

    public function test_reschedule_success_logs_audit(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();
        $date = now()->addWeeks(2)->toDateString();

        $this->postJson("/v1/edu/lesson/reschedule/{$id}", [
            'lesson_date' => $date,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.start_time', '09:00:00');

        $this->assertDatabaseHas('edu_lesson_histories', ['lesson_id' => $id, 'action' => 'reschedule']);
    }

    public function test_cancel_lesson(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();

        $this->postJson("/v1/edu/lesson/cancel/{$id}", ['reason' => 'Giáo viên nghỉ'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('edu_lesson_histories', ['lesson_id' => $id, 'action' => 'cancel']);
    }

    public function test_cannot_cancel_completed_lesson(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();
        DB::table('edu_lessons')->where('id', $id)->update(['status' => 'completed']);

        $this->postJson("/v1/edu/lesson/cancel/{$id}", ['reason' => 'x'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_complete_marks_lesson_completed_and_logs_audit(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();

        $this->postJson("/v1/edu/lesson/complete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('edu_lesson_histories', ['lesson_id' => $id, 'action' => 'complete']);
    }

    public function test_cannot_complete_cancelled_or_locked_lesson(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();
        DB::table('edu_lessons')->where('id', $id)->update(['status' => 'cancelled']);

        $this->postJson("/v1/edu/lesson/complete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_lock_requires_completed_then_unlock_with_reason(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();

        // Cannot lock a scheduled lesson.
        $this->postJson("/v1/edu/lesson/lock/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        DB::table('edu_lessons')->where('id', $id)->update(['status' => 'completed']);

        $this->postJson("/v1/edu/lesson/lock/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'locked');

        $this->assertDatabaseHas('edu_lesson_histories', ['lesson_id' => $id, 'action' => 'lock']);

        // Locked lessons cannot be updated (BR005).
        $this->putJson("/v1/edu/lesson/update/{$id}", ['lesson_note' => 'x'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);

        // Unlock requires a reason.
        $this->postJson("/v1/edu/lesson/unlock/{$id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->postJson("/v1/edu/lesson/unlock/{$id}", ['reason' => 'Cần sửa điểm danh'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('edu_lesson_histories', ['lesson_id' => $id, 'action' => 'unlock']);
    }

    public function test_list_filters_by_status_and_class(): void
    {
        $this->actingAsAdmin();

        $classId = $this->makeClassId();
        $a = $this->createLesson(['class_room_id' => $classId]);
        $this->createLesson(['class_room_id' => $classId, 'lesson_title' => 'B']);
        DB::table('edu_lessons')->where('id', $a)->update(['status' => 'completed']);

        $this->getJson("/v1/edu/lesson/list?class_room_id={$classId}")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson("/v1/edu/lesson/list?class_room_id={$classId}&status=completed")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_detail_includes_histories(): void
    {
        $this->actingAsAdmin();

        $id = $this->createLesson();
        $this->postJson("/v1/edu/lesson/cancel/{$id}", ['reason' => 'Nghỉ']);

        $this->getJson("/v1/edu/lesson/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.lesson.id', $id)
            ->assertJsonStructure(['data' => ['lesson' => ['histories', 'objective', 'is_locked']]]);
    }

    public function test_auto_complete_finishes_only_past_active_lessons(): void
    {
        $past = $this->createLesson(['lesson_date' => now()->subDay()->toDateString(), 'status' => 'scheduled']);
        $future = $this->createLesson(['lesson_date' => now()->addWeek()->toDateString(), 'status' => 'scheduled']);
        $cancelled = $this->createLesson(['lesson_date' => now()->subDay()->toDateString(), 'status' => 'cancelled']);

        $count = app(LessonService::class)->autoComplete();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('edu_lessons', ['id' => $past, 'status' => 'completed']);
        $this->assertNotNull(DB::table('edu_lessons')->where('id', $past)->value('completed_at'));
        $this->assertDatabaseHas('edu_lessons', ['id' => $future, 'status' => 'scheduled']);
        $this->assertDatabaseHas('edu_lessons', ['id' => $cancelled, 'status' => 'cancelled']);
        $this->assertDatabaseHas('edu_lesson_histories', ['lesson_id' => $past, 'action' => 'auto_complete']);
    }

    public function test_auto_lock_locks_only_lessons_past_the_window(): void
    {
        config(['education.lesson_auto_lock_days' => 7]);

        $stale = $this->createLesson(['status' => 'completed', 'completed_at' => now()->subDays(8)]);
        $recent = $this->createLesson(['status' => 'completed', 'completed_at' => now()->subDays(1)]);

        $count = app(LessonService::class)->autoLock();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('edu_lessons', ['id' => $stale, 'status' => 'locked']);
        $this->assertDatabaseHas('edu_lessons', ['id' => $recent, 'status' => 'completed']);
        $this->assertDatabaseHas('edu_lesson_histories', ['lesson_id' => $stale, 'action' => 'auto_lock']);
    }

    public function test_auto_progress_command_runs(): void
    {
        $this->createLesson(['lesson_date' => now()->subDay()->toDateString(), 'status' => 'scheduled']);

        $this->artisan('lessons:auto-progress')->assertExitCode(0);
    }
}
