<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class TimesheetTest extends TestCase
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
        $this->courseId = DB::table('edu_courses')->insertGetId([
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

    private function makeClassId(string $learningType = 'scheduled'): int
    {
        return DB::table('edu_classes')->insertGetId([
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'Class '.uniqid(),
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'learning_type' => $learningType,
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
            'code' => 'S_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeSessionId(int $classId, int $teacherId, string $date, string $start, string $end): int
    {
        return DB::table('edu_sessions')->insertGetId([
            'business_id' => $this->businessId,
            'class_id' => $classId,
            'teacher_id' => $teacherId,
            'session_no' => 1,
            'code' => 'SES_'.strtoupper(uniqid()),
            'name' => 'Session '.uniqid(),
            'session_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeAttendance(int $sessionId, string $status): void
    {
        DB::table('edu_attendances')->insert([
            'session_id' => $sessionId,
            'student_id' => $this->makeStudentId(),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array{0: User, 1: int} [acting user, hr_teachers id] */
    private function actingAsTeacher(): array
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, ['timesheet.view']);
        $user = $this->makeUser(false, $roleId, $this->businessId);

        $teacherId = DB::table('hr_teachers')->insertGetId([
            'user_id' => $user->id,
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Teacher '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsApi($user);

        return [$user, $teacherId];
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/hr/timesheet/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/hr/timesheet/list')->assertJsonPath('code', 403);
    }

    public function test_session_without_attendance_is_excluded(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $classId = $this->makeClassId();
        $this->makeSessionId($classId, $teacherId, now()->toDateString(), '19:00:00', '20:30:00');

        $this->getJson('/v1/hr/timesheet/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }

    public function test_session_with_attendance_counts_as_worked(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $classId = $this->makeClassId('scheduled');
        $sessionId = $this->makeSessionId($classId, $teacherId, now()->toDateString(), '19:00:00', '20:30:00');
        $this->makeAttendance($sessionId, 'present');
        $this->makeAttendance($sessionId, 'absent');

        $response = $this->getJson('/v1/hr/timesheet/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.hours', 1.5)
            ->assertJsonPath('data.items.0.learning_type', 'scheduled')
            ->assertJsonPath('data.items.0.present_count', 1)
            ->assertJsonPath('data.items.0.absent_count', 1);

        $response->assertJsonPath('data.items.0.attendances_count', 2);
    }

    public function test_summary_aggregates_hours_by_learning_type(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $classA = $this->makeClassId('scheduled');
        $classB = $this->makeClassId('flexible');

        $s1 = $this->makeSessionId($classA, $teacherId, '2026-07-01', '19:00:00', '20:30:00');
        $this->makeAttendance($s1, 'present');
        $s2 = $this->makeSessionId($classB, $teacherId, '2026-07-02', '08:00:00', '09:00:00');
        $this->makeAttendance($s2, 'present');
        $this->makeAttendance($s2, 'present');

        $this->getJson('/v1/hr/timesheet/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.total_sessions', 2)
            ->assertJsonPath('data.total_hours', 2.5)
            ->assertJsonPath('data.hours_by_type.scheduled', 1.5)
            ->assertJsonPath('data.hours_by_type.flexible', 1)
            ->assertJsonPath('data.attendance_rate', 100);
    }

    public function test_only_own_sessions_are_returned(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $otherTeacherId = DB::table('hr_teachers')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Other Teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classId = $this->makeClassId();
        $ownSession = $this->makeSessionId($classId, $teacherId, now()->toDateString(), '19:00:00', '20:00:00');
        $this->makeAttendance($ownSession, 'present');
        $otherSession = $this->makeSessionId($classId, $otherTeacherId, now()->toDateString(), '10:00:00', '11:00:00');
        $this->makeAttendance($otherSession, 'present');

        $this->getJson('/v1/hr/timesheet/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $ownSession);
    }

    public function test_date_range_filters_sessions(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $classId = $this->makeClassId();
        $inRange = $this->makeSessionId($classId, $teacherId, '2026-07-10', '19:00:00', '20:00:00');
        $this->makeAttendance($inRange, 'present');
        $outOfRange = $this->makeSessionId($classId, $teacherId, '2026-06-01', '19:00:00', '20:00:00');
        $this->makeAttendance($outOfRange, 'present');

        $this->getJson('/v1/hr/timesheet/list?date_from=2026-07-01&date_to=2026-07-31')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $inRange);
    }

    public function test_non_teaching_account_gets_empty_result_not_error(): void
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, ['timesheet.view']);
        $this->actingAsApi($this->makeUser(false, $roleId, $this->businessId));

        $this->getJson('/v1/hr/timesheet/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);

        $this->getJson('/v1/hr/timesheet/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.total_sessions', 0);
    }
}
