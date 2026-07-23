<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function makeBranchId(int $businessId): int
    {
        return DB::table('sys_branches')->insertGetId([
            'business_id' => $businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'CN_'.strtoupper(uniqid()),
            'address' => '123 Le Loi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeLevelId(): int
    {
        return DB::table('edu_levels')->insertGetId([
            'level_code' => 'A1_'.strtoupper(uniqid()),
            'level_name' => 'A1',
            'level_order' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function studentPayload(int $businessId, int $branchId, int $levelId, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyen Van A',
            'dob' => '2010-05-12',
            'gender' => 'male',
            'email' => 'a-'.uniqid().'@gmail.com',
            'phone' => '0901234567',
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'level_id' => $levelId,
            'enrollment_date' => '2026-06-01',
            'address' => '123 Le Loi',
            'province' => 'Ho Chi Minh',
            'district' => 'District 7',
        ], $overrides);
    }

    public function test_a_business_cannot_list_another_businesses_students(): void
    {
        $bizA = $this->makeBusinessId();
        $bizB = $this->makeBusinessId();
        $levelId = $this->makeLevelId();

        $this->actingAsAdmin($bizA);
        $branchA = $this->makeBranchId($bizA);
        $studentA = $this->postJson('/v1/edu/student/create', $this->studentPayload($bizA, $branchA, $levelId))
            ->assertStatus(200)
            ->json('data.id');

        // Business B sees none of A's students.
        $this->actingAsAdmin($bizB);
        $this->getJson('/v1/edu/student/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }

    public function test_a_business_cannot_read_another_businesses_student_detail(): void
    {
        $bizA = $this->makeBusinessId();
        $bizB = $this->makeBusinessId();
        $levelId = $this->makeLevelId();

        $this->actingAsAdmin($bizA);
        $branchA = $this->makeBranchId($bizA);
        $studentA = $this->postJson('/v1/edu/student/create', $this->studentPayload($bizA, $branchA, $levelId))
            ->assertStatus(200)
            ->json('data.id');

        // The scope hides A's student from B entirely: findOrFail 404s rather
        // than leaking that the record exists.
        $this->actingAsAdmin($bizB);
        $this->getJson("/v1/edu/student/detail/{$studentA}")
            ->assertStatus(404);
    }

    public function test_a_business_cannot_see_another_businesses_users(): void
    {
        $bizA = $this->makeBusinessId();
        $bizB = $this->makeBusinessId();
        $roleA = $this->makeRoleId($bizA);

        $this->actingAsAdmin($bizA);
        $createdUserId = $this->postJson('/v1/sys/user/create', [
            'full_name' => 'Staff A',
            'email' => 'staff-'.uniqid().'@hana.edu.vn',
            'phone' => '09'.substr((string) microtime(true), -8),
            'username' => 'staffa_'.uniqid(),
            'password' => 'Abc@1234',
            'password_confirmation' => 'Abc@1234',
            'business_id' => $bizA,
            'role_id' => $roleA,
            'status' => 'active',
        ])->assertStatus(200)->json('data.id');

        // Admin of B sees none of A's users and cannot fetch one by id.
        $this->actingAsAdmin($bizB);
        $this->getJson('/v1/sys/user/list?search=Staff A')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
        $this->getJson("/v1/sys/user/detail/{$createdUserId}")
            ->assertStatus(404);
    }

    public function test_a_business_cannot_see_another_businesses_courses(): void
    {
        $bizA = $this->makeBusinessId();
        $bizB = $this->makeBusinessId();

        $this->actingAsAdmin($bizA);
        $courseA = $this->postJson('/v1/edu/course/create', [
            'name' => 'IELTS Foundation',
            'code' => 'CRS'.strtoupper(bin2hex(random_bytes(4))),
            'duration_minutes' => 90,
            'price_per_lesson' => 250000,
            'business_id' => $bizA,
        ])->assertStatus(200)->json('data.id');

        // Business B sees none of A's courses and cannot fetch one by id.
        $this->actingAsAdmin($bizB);
        $this->getJson('/v1/edu/course/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
        $this->getJson("/v1/edu/course/detail/{$courseA}")
            ->assertStatus(404);
    }

    public function test_a_business_cannot_see_another_businesses_parents(): void
    {
        $bizA = $this->makeBusinessId();
        $bizB = $this->makeBusinessId();

        $this->actingAsAdmin($bizA);
        $branchA = $this->makeBranchId($bizA);
        $parentA = $this->postJson('/v1/crm/parent/create', [
            'name' => 'Robert Smith',
            'gender' => 'male',
            'phone' => '0922222222',
            'email' => 'parent-'.uniqid().'@example.com',
            'business_id' => $bizA,
            'branch_id' => $branchA,
            'address' => '123 Le Loi',
        ])->assertStatus(200)->json('data.id');

        // Business B sees none of A's parents and cannot fetch one by id.
        $this->actingAsAdmin($bizB);
        $this->getJson('/v1/crm/parent/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
        $this->getJson("/v1/crm/parent/detail/{$parentA}")
            ->assertStatus(404);
    }

    public function test_a_business_cannot_see_another_businesses_levels(): void
    {
        $bizA = $this->makeBusinessId();
        $bizB = $this->makeBusinessId();

        $this->actingAsAdmin($bizA);
        $courseA = $this->postJson('/v1/edu/course/create', [
            'name' => 'IELTS Foundation',
            'code' => 'CRS'.strtoupper(bin2hex(random_bytes(4))),
            'duration_minutes' => 90,
            'price_per_lesson' => 250000,
            'business_id' => $bizA,
        ])->assertStatus(200)->json('data.id');
        $levelA = $this->postJson('/v1/edu/level/create', [
            'level_code' => 'A1_'.strtoupper(uniqid()),
            'level_name' => 'A1',
            'course_id' => $courseA,
            'level_order' => 1,
        ])->assertStatus(200)->json('data.id');

        // Business B sees none of A's levels and cannot fetch one by id.
        $this->actingAsAdmin($bizB);
        $this->getJson('/v1/edu/level/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
        $this->getJson("/v1/edu/level/detail/{$levelA}")
            ->assertStatus(404);
    }

    public function test_created_student_is_stamped_with_acting_business_ignoring_payload(): void
    {
        $bizA = $this->makeBusinessId();
        $bizForged = $this->makeBusinessId();
        $levelId = $this->makeLevelId();

        $this->actingAsAdmin($bizA);
        $branchA = $this->makeBranchId($bizA);

        // Client attempts to forge another business_id in the payload.
        $id = $this->postJson('/v1/edu/student/create', $this->studentPayload($bizForged, $branchA, $levelId))
            ->assertStatus(200)
            ->json('data.id');

        // The row lands in the acting admin's business, not the forged one.
        $this->assertDatabaseHas('edu_students', ['id' => $id, 'business_id' => $bizA]);
        $this->assertDatabaseMissing('edu_students', ['id' => $id, 'business_id' => $bizForged]);
    }
}
