<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class AttendanceTest extends TestCase
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
            'name' => 'Attendance Class',
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'learning_type' => 'flexible',
            'status' => 'upcoming',
            'start_date' => now()->toDateString(),
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

    private function makeSessionId(string $date, ?int $classId = null): int
    {
        return DB::table('edu_sessions')->insertGetId([
            'business_id' => $this->businessId,
            'class_id' => $classId ?? $this->classId,
            'session_no' => 1,
            'name' => 'Session '.uniqid(),
            'session_date' => $date,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeAttendanceId(int $sessionId, int $studentId, string $status): int
    {
        return DB::table('edu_attendances')->insertGetId([
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/attendance/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/attendance/list')->assertJsonPath('code', 403);
    }

    public function test_manager_with_permission_can_list(): void
    {
        $this->actingAsManager(['attendance.list']);

        $this->getJson('/v1/edu/attendance/list')->assertStatus(200);
    }

    public function test_list_returns_records_with_session_and_student(): void
    {
        $this->actingAsAdmin();

        $sessionId = $this->makeSessionId(now()->toDateString());
        $studentId = $this->makeStudentId();
        $this->makeAttendanceId($sessionId, $studentId, 'present');

        $this->getJson('/v1/edu/attendance/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.status', 'present')
            ->assertJsonPath('data.items.0.status_label', 'Có mặt')
            ->assertJsonPath('data.items.0.session.id', $sessionId)
            ->assertJsonPath('data.items.0.student.id', $studentId);
    }

    public function test_list_filters_by_status(): void
    {
        $this->actingAsAdmin();

        $sessionId = $this->makeSessionId(now()->toDateString());
        $this->makeAttendanceId($sessionId, $this->makeStudentId(), 'present');
        $this->makeAttendanceId($sessionId, $this->makeStudentId(), 'absent');

        $this->getJson('/v1/edu/attendance/list?status=absent')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.status', 'absent');
    }

    public function test_list_filters_by_class_and_session_date(): void
    {
        $this->actingAsAdmin();

        $todaySession = $this->makeSessionId(now()->toDateString());
        $oldSession = $this->makeSessionId(now()->subMonth()->toDateString());
        $otherClassSession = $this->makeSessionId(now()->toDateString(), $this->makeClassId());

        $this->makeAttendanceId($todaySession, $this->makeStudentId(), 'present');
        $this->makeAttendanceId($oldSession, $this->makeStudentId(), 'late');
        $this->makeAttendanceId($otherClassSession, $this->makeStudentId(), 'excused');

        $this->getJson('/v1/edu/attendance/list?class_id='.$this->classId.'&date='.now()->toDateString())
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.session.id', $todaySession);
    }

    public function test_list_searches_by_student_name(): void
    {
        $this->actingAsAdmin();

        $sessionId = $this->makeSessionId(now()->toDateString());

        $needleId = DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'S_FIND',
            'name' => 'Findme Nguyen',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->makeAttendanceId($sessionId, $needleId, 'present');
        $this->makeAttendanceId($sessionId, $this->makeStudentId(), 'absent');

        $this->getJson('/v1/edu/attendance/list?search=Findme')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.student.id', $needleId);
    }
}
