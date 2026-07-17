<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class TeacherReportTest extends TestCase
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

    private function makeClassId(int $teacherId): int
    {
        return DB::table('edu_classes')->insertGetId([
            'code' => 'CLS_'.strtoupper(uniqid()),
            'name' => 'Class '.uniqid(),
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'teacher_id' => $teacherId,
            'learning_type' => 'scheduled',
            'status' => 'active',
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

    private function makeSessionId(int $classId, string $date, string $start, string $end): int
    {
        return DB::table('edu_sessions')->insertGetId([
            'business_id' => $this->businessId,
            'class_id' => $classId,
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
        $this->grantPermissions($roleId, ['teacher_report.view']);
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
        $this->getJson('/v1/edu/teacher-report/summary')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/teacher-report/summary')->assertJsonPath('code', 403);
    }

    public function test_summary_with_no_classes_returns_zeroed_overview(): void
    {
        $this->actingAsTeacher();

        $this->getJson('/v1/edu/teacher-report/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.overview.total_students', 0)
            ->assertJsonPath('data.overview.total_sessions', 0)
            ->assertJsonPath('data.overview.attendance_rate', 0)
            ->assertJsonPath('data.score_by_class', [])
            ->assertJsonPath('data.weekly_sessions', []);
    }

    public function test_summary_aggregates_attendance_and_sessions(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $classId = $this->makeClassId($teacherId);

        $s1 = $this->makeSessionId($classId, '2026-07-01', '19:00:00', '20:30:00');
        $this->makeAttendance($s1, 'present');
        $this->makeAttendance($s1, 'absent');
        $s2 = $this->makeSessionId($classId, '2026-07-08', '19:00:00', '20:00:00');
        $this->makeAttendance($s2, 'present');

        $this->getJson('/v1/edu/teacher-report/summary?date_from=2026-07-01&date_to=2026-07-31')
            ->assertStatus(200)
            ->assertJsonPath('data.overview.total_sessions', 2)
            ->assertJsonPath('data.overview.attendance_rate', 66.7)
            ->assertJsonPath('data.attendance_breakdown.present', 2)
            ->assertJsonPath('data.attendance_breakdown.absent', 1)
            ->assertJsonPath('data.attendance_breakdown.total', 3);
    }

    public function test_class_id_filter_excludes_other_classes(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $classA = $this->makeClassId($teacherId);
        $classB = $this->makeClassId($teacherId);

        $sessionA = $this->makeSessionId($classA, '2026-07-01', '19:00:00', '20:00:00');
        $this->makeAttendance($sessionA, 'present');
        $sessionB = $this->makeSessionId($classB, '2026-07-01', '19:00:00', '20:00:00');
        $this->makeAttendance($sessionB, 'present');

        $this->getJson("/v1/edu/teacher-report/summary?class_id={$classA}&date_from=2026-07-01&date_to=2026-07-31")
            ->assertStatus(200)
            ->assertJsonPath('data.overview.total_sessions', 1);
    }

    public function test_only_own_classes_are_reported(): void
    {
        [, $teacherId] = $this->actingAsTeacher();
        $otherTeacherId = DB::table('hr_teachers')->insertGetId([
            'business_id' => $this->businessId,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Other Teacher',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ownClass = $this->makeClassId($teacherId);
        $otherClass = $this->makeClassId($otherTeacherId);

        $ownSession = $this->makeSessionId($ownClass, '2026-07-01', '19:00:00', '20:00:00');
        $this->makeAttendance($ownSession, 'present');
        $otherSession = $this->makeSessionId($otherClass, '2026-07-01', '10:00:00', '11:00:00');
        $this->makeAttendance($otherSession, 'present');

        $this->getJson('/v1/edu/teacher-report/summary?date_from=2026-07-01&date_to=2026-07-31')
            ->assertStatus(200)
            ->assertJsonPath('data.overview.total_sessions', 1);
    }
}
