<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
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
            'name' => 'Leave Class',
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'learning_type' => 'flexible',
            'status' => 'upcoming',
            'start_date' => now()->addDays(7)->toDateString(),
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

    private function makeLessonId(array $overrides = []): int
    {
        static $no = 0;
        $no++;

        return DB::table('edu_lessons')->insertGetId(array_merge([
            'class_room_id' => $this->classId,
            'lesson_no' => $no,
            'lesson_title' => 'Lesson '.uniqid(),
            'lesson_date' => now()->addDays(3)->toDateString(),
            'start_time' => '19:00',
            'end_time' => '20:30',
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createStudentLeave(array $overrides = []): TestResponse
    {
        $lessonId = $overrides['lesson_id'] ?? $this->makeLessonId();
        $lessonDate = DB::table('edu_lessons')->where('id', $lessonId)->value('lesson_date');

        return $this->postJson('/v1/edu/leave/create', array_merge([
            'request_type' => 'student_leave',
            'requester_id' => $this->makeStudentId(),
            'lesson_id' => $lessonId,
            'leave_date' => date('Y-m-d', strtotime($lessonDate)),
            'reason_type' => 'sick',
            'reason' => 'Sốt cao',
        ], $overrides));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/leave/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/leave/list')->assertJsonPath('code', 403);
    }

    public function test_create_starts_pending_with_generated_code(): void
    {
        $this->actingAsAdmin();

        $this->createStudentLeave()
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.request_type', 'student_leave')
            ->assertJsonPath('data.requester_type', 'student')
            ->assertJsonPath('data.request_code', 'LR000001');
    }

    public function test_create_rejected_for_completed_lesson(): void
    {
        $this->actingAsAdmin();

        $lessonId = $this->makeLessonId(['status' => 'completed']);

        $this->createStudentLeave(['lesson_id' => $lessonId])
            ->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Không thể tạo đơn cho buổi học đã hoàn thành.');
    }

    public function test_create_rejected_for_cancelled_lesson(): void
    {
        $this->actingAsAdmin();

        $lessonId = $this->makeLessonId(['status' => 'cancelled']);

        $this->createStudentLeave(['lesson_id' => $lessonId])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Không thể tạo đơn cho buổi học đã bị hủy.');
    }

    public function test_create_rejected_when_date_mismatches_lesson(): void
    {
        $this->actingAsAdmin();

        $lessonId = $this->makeLessonId();

        $this->createStudentLeave([
            'lesson_id' => $lessonId,
            'leave_date' => now()->addDays(30)->toDateString(),
        ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Ngày nghỉ phải trùng với ngày của buổi học.');
    }

    public function test_create_rejected_for_duplicate_lesson(): void
    {
        $this->actingAsAdmin();

        $lessonId = $this->makeLessonId();
        $studentId = $this->makeStudentId();

        $this->createStudentLeave(['lesson_id' => $lessonId, 'requester_id' => $studentId])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        $this->createStudentLeave(['lesson_id' => $lessonId, 'requester_id' => $studentId])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Đã tồn tại đơn nghỉ cho buổi học này.');
    }

    public function test_approve_student_leave_raises_makeup_entitlement(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudentLeave()->json('data.id');

        $this->postJson("/v1/edu/leave/approve/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonCount(1, 'data.makeups')
            ->assertJsonPath('data.makeups.0.status', 'waiting');
    }

    public function test_approve_rejected_when_lesson_already_occurred(): void
    {
        $this->actingAsAdmin();

        $lessonId = $this->makeLessonId([
            'lesson_date' => now()->subDays(2)->toDateString(),
            'status' => 'scheduled',
        ]);

        $id = $this->createStudentLeave([
            'lesson_id' => $lessonId,
            'leave_date' => now()->subDays(2)->toDateString(),
        ])->json('data.id');

        $this->postJson("/v1/edu/leave/approve/{$id}")
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Không thể duyệt đơn cho buổi học đã diễn ra.');
    }

    public function test_reject_records_reason(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudentLeave()->json('data.id');

        $this->postJson("/v1/edu/leave/reject/{$id}", ['rejection_reason' => 'Không hợp lệ'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Không hợp lệ');
    }

    public function test_cancel_expires_waiting_makeups(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudentLeave()->json('data.id');
        $this->postJson("/v1/edu/leave/approve/{$id}")->assertStatus(200);

        $this->postJson("/v1/edu/leave/cancel/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.makeups.0.status', 'expired');
    }

    public function test_schedule_makeup_assigns_a_lesson(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudentLeave()->json('data.id');
        $makeupId = $this->postJson("/v1/edu/leave/approve/{$id}")
            ->json('data.makeups.0.id');

        $makeupLessonId = $this->makeLessonId();

        $this->postJson("/v1/edu/leave/makeup/schedule/{$makeupId}", ['makeup_lesson_id' => $makeupLessonId])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.makeup_lesson_id', $makeupLessonId);
    }

    public function test_update_only_allowed_while_pending(): void
    {
        $this->actingAsAdmin();

        $id = $this->createStudentLeave()->json('data.id');

        $this->putJson("/v1/edu/leave/update/{$id}", ['reason' => 'Cập nhật lý do'])
            ->assertStatus(200)
            ->assertJsonPath('data.reason', 'Cập nhật lý do');

        $this->postJson("/v1/edu/leave/approve/{$id}")->assertStatus(200);

        $this->putJson("/v1/edu/leave/update/{$id}", ['reason' => 'Sau duyệt'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Chỉ có thể chỉnh sửa đơn đang chờ duyệt.');
    }

    public function test_create_teacher_leave(): void
    {
        $this->actingAsAdmin();

        $lessonId = $this->makeLessonId();
        $lessonDate = DB::table('edu_lessons')->where('id', $lessonId)->value('lesson_date');

        $this->postJson('/v1/edu/leave/create', [
            'request_type' => 'teacher_leave',
            'requester_id' => $this->makeTeacherId(),
            'lesson_id' => $lessonId,
            'leave_date' => date('Y-m-d', strtotime($lessonDate)),
            'reason_type' => 'personal',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.requester_type', 'teacher')
            ->assertJsonCount(0, 'data.makeups');
    }
}
