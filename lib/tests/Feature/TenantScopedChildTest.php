<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

/**
 * Cross-tenant isolation for the child tables that were given a business_id and
 * BelongsToBusiness (migration 2026_07_15_000005). Beyond "can't reach another
 * tenant's parent", these prove the scope actively filters child rows by
 * business_id — a row stamped with another business is excluded even when it
 * points at one of the acting business's own parents.
 */
class TenantScopedChildTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function branchId(int $biz): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $biz,
            'name' => 'Branch '.uniqid(),
            'code' => 'BR_'.strtoupper(uniqid()),
            'address' => '1 Test St',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function courseId(int $biz): int
    {
        return DB::table('edu_courses')->insertGetId([
            'business_id' => $biz,
            'name' => 'Course '.uniqid(),
            'code' => 'C_'.strtoupper(uniqid()),
            'duration_minutes' => 90,
            'price_per_lesson' => 100000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function roomId(int $biz): int
    {
        return DB::table('edu_rooms')->insertGetId([
            'business_id' => $biz,
            'branch_id' => $this->branchId($biz),
            'room_code' => 'R_'.strtoupper(uniqid()),
            'room_name' => 'Room '.uniqid(),
            'capacity' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function classId(int $biz, ?int $roomId = null): int
    {
        return DB::table('edu_classes')->insertGetId([
            'course_id' => $this->courseId($biz),
            'business_id' => $biz,
            'room_id' => $roomId,
            'name' => 'Class '.uniqid(),
            'code' => 'CLS_'.strtoupper(uniqid()),
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function sessionId(int $biz, int $classId, ?int $roomId = null): int
    {
        return DB::table('edu_sessions')->insertGetId([
            'business_id' => $biz,
            'class_id' => $classId,
            'room_id' => $roomId,
            'session_no' => 1,
            'name' => 'Session '.uniqid(),
            'session_date' => now()->toDateString(),
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function teacherId(int $biz): int
    {
        return DB::table('hr_teachers')->insertGetId([
            'business_id' => $biz,
            'code' => 'T_'.strtoupper(uniqid()),
            'full_name' => 'Teacher '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function contract(int $biz, int $teacherId): void
    {
        DB::table('hr_contracts')->insert([
            'business_id' => $biz,
            'teacher_id' => $teacherId,
            'type' => 'full_time',
            'start_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_class_session_detail_is_business_scoped(): void
    {
        $bizA = $this->makeBusinessId();
        $bizB = $this->makeBusinessId();

        $classA = $this->classId($bizA);
        $sessionA = $this->sessionId($bizA, $classA);

        // Business B cannot read business A's session: the scope hides it, so
        // findOrFail 404s.
        $this->actingAsAdmin($bizB);
        $this->getJson("/v1/edu/class-session/detail/{$sessionA}")->assertStatus(404);
    }

    public function test_room_session_count_excludes_other_business_rows(): void
    {
        $bizB = $this->makeBusinessId();
        $bizA = $this->makeBusinessId();

        $this->actingAsAdmin($bizB);

        $roomB = $this->roomId($bizB);
        $classB = $this->classId($bizB, $roomB);

        // One session in B, one stamped with A but pointing at B's room.
        $this->sessionId($bizB, $classB, $roomB);
        $this->sessionId($bizA, $classB, $roomB);

        // The stat counts only B's session — the scope filters by business_id,
        // not just room_id.
        $this->getJson("/v1/edu/room/detail/{$roomB}")
            ->assertStatus(200)
            ->assertJsonPath('data.statistics.total_sessions', 1);
    }

    public function test_teacher_contract_count_excludes_other_business_rows(): void
    {
        $bizB = $this->makeBusinessId();
        $bizA = $this->makeBusinessId();

        $this->actingAsAdmin($bizB);

        $teacherB = $this->teacherId($bizB);

        // One contract in B, one stamped with A but pointing at B's teacher.
        $this->contract($bizB, $teacherB);
        $this->contract($bizA, $teacherB);

        $this->getJson("/v1/hr/teacher/detail/{$teacherB}")
            ->assertStatus(200)
            ->assertJsonPath('data.statistics.total_contracts', 1);
    }
}
