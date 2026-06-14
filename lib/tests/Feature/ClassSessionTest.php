<?php

namespace Tests\Feature;

use App\Modules\Education\ClassSession\Models\ClassSession;
use Carbon\Carbon;
use Database\Seeders\ClassSessionPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class ClassSessionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $courseId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClassSessionPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = DB::table('edu_courses')->insertGetId([
            'name' => 'IELTS Foundation',
            'code' => 'IELTS_F_'.uniqid(),
            'duration_minutes' => 90,
            'price_per_lesson' => 250000,
            'is_active' => true,
            'business_id' => $this->businessId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassId(array $overrides = []): int
    {
        $payload = array_merge([
            'name' => 'IELTS Foundation - Khai giảng tháng 7',
            'code' => 'IELTS-F-'.uniqid(),
            'course_id' => $this->courseId,
            'learning_type' => 'flexible',
            'start_date' => now()->addDays(10)->toDateString(),
        ], $overrides);

        return $this->postJson('/v1/edu/class-room/create', $payload)->json('data.id');
    }

    private function sessionPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Buổi 1 - Introduction',
            'session_date' => '2026-07-02',
            'start_time' => '19:00',
            'end_time' => '20:30',
        ], $overrides);
    }

    private function createSession(int $classId, array $overrides = []): TestResponse
    {
        return $this->postJson("/v1/edu/class-room/{$classId}/session/create", $this->sessionPayload($overrides));
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/class-room/1/session/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/class-room/1/session/list')->assertJsonPath('code', 403);
    }

    public function test_manager_with_permission_can_access(): void
    {
        $this->actingAsManager(['session.list']);

        $this->getJson('/v1/edu/class-room/1/session/list')->assertJsonPath('success', true);
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function test_can_create_session(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();

        $response = $this->createSession($classId);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_no', 1)
            ->assertJsonPath('data.class_id', $classId)
            ->assertJsonPath('data.status', 'upcoming');

        $this->assertDatabaseHas('edu_sessions', [
            'class_id' => $classId,
            'name' => 'Buổi 1 - Introduction',
            'status' => 'upcoming',
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();

        $this->postJson("/v1/edu/class-room/{$classId}/session/create", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'session_date', 'start_time', 'end_time']);
    }

    public function test_create_rejects_end_before_start(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();

        $this->createSession($classId, ['start_time' => '20:30', 'end_time' => '19:00'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_time');
    }

    public function test_create_rejects_schedule_conflict(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();

        $this->createSession($classId)->assertStatus(200);

        // Overlapping window for the same class → conflict.
        $this->createSession($classId, ['name' => 'Buổi trùng', 'start_time' => '20:00', 'end_time' => '21:00'])
            ->assertJsonPath('success', false);

        // Non-overlapping window is fine.
        $this->createSession($classId, ['name' => 'Buổi sau', 'start_time' => '21:00', 'end_time' => '22:00'])
            ->assertJsonPath('success', true);
    }

    // ── List / detail ─────────────────────────────────────────────────────────

    public function test_list_is_scoped_to_the_class_and_filters_by_status(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $otherClassId = $this->makeClassId(['code' => 'OTHER-'.uniqid()]);

        $this->createSession($classId);
        $this->createSession($otherClassId);

        $this->getJson("/v1/edu/class-room/{$classId}/session/list")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);

        $this->getJson("/v1/edu/class-room/{$classId}/session/list?status=cancelled")
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 0);
    }

    public function test_detail_returns_session(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();

        $id = $this->createSession($classId)->json('data.id');

        $this->getJson("/v1/edu/class-session/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.code', fn ($code) => is_string($code) && str_contains($code, 'B01'));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_can_update_session(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $id = $this->createSession($classId)->json('data.id');

        $this->putJson("/v1/edu/class-session/update/{$id}", ['name' => 'Buổi đổi tên', 'note' => 'Ghi chú'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Buổi đổi tên');

        $this->assertDatabaseHas('edu_sessions', ['id' => $id, 'name' => 'Buổi đổi tên', 'note' => 'Ghi chú']);
    }

    public function test_update_blocked_when_attendance_locked(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $id = $this->createSession($classId)->json('data.id');

        DB::table('edu_sessions')->where('id', $id)->update(['attendance_locked' => true]);

        $this->putJson("/v1/edu/class-session/update/{$id}", ['name' => 'Không được đổi'])
            ->assertJsonPath('success', false);
    }

    public function test_update_syncs_tags(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $id = $this->createSession($classId)->json('data.id');

        $tagId = DB::table('crm_tags')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Quan trọng',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->putJson("/v1/edu/class-session/update/{$id}", ['tag_ids' => [$tagId]])
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.tags');

        $this->assertDatabaseHas('edu_session_tags', ['session_id' => $id, 'tag_id' => $tagId]);
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function test_can_cancel_session(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $id = $this->createSession($classId)->json('data.id');

        $this->postJson("/v1/edu/class-session/cancel/{$id}", ['reason' => 'Giáo viên nghỉ bệnh'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('edu_sessions', ['id' => $id, 'status' => 'cancelled']);

        // Cancelling again is rejected.
        $this->postJson("/v1/edu/class-session/cancel/{$id}", ['reason' => 'again'])
            ->assertJsonPath('success', false);
    }

    public function test_cancel_requires_reason(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $id = $this->createSession($classId)->json('data.id');

        $this->postJson("/v1/edu/class-session/cancel/{$id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_delete_soft_deletes_session(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $id = $this->createSession($classId)->json('data.id');

        $this->deleteJson("/v1/edu/class-session/delete/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('edu_sessions', ['id' => $id]);
    }

    // ── Generate ──────────────────────────────────────────────────────────────

    public function test_generate_creates_sessions_from_schedules(): void
    {
        $this->actingAsAdmin();

        $date = Carbon::parse('2026-07-15');
        $weekday = $date->dayOfWeekIso;

        $classId = $this->makeClassId([
            'schedules' => [
                ['weekday' => $weekday, 'start_time' => '19:00', 'end_time' => '20:30'],
            ],
        ]);

        $this->postJson("/v1/edu/class-room/{$classId}/session/generate", [
            'from_date' => $date->toDateString(),
            'to_date' => $date->toDateString(),
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.skipped', 0);

        $this->assertEquals(1, DB::table('edu_sessions')
            ->where('class_id', $classId)
            ->whereDate('session_date', $date->toDateString())
            ->where('start_time', '19:00')
            ->count());

        // Re-running skips the already-generated session.
        $this->postJson("/v1/edu/class-room/{$classId}/session/generate", [
            'from_date' => $date->toDateString(),
            'to_date' => $date->toDateString(),
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.created', 0)
            ->assertJsonPath('data.skipped', 1);
    }

    // ── Relations (edu_attendances / edu_session_feedbacks) ─────────────────────

    public function test_session_links_attendances_and_feedbacks(): void
    {
        $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $id = $this->createSession($classId)->json('data.id');

        $studentId = DB::table('edu_students')->insertGetId([
            'code' => 'STD_'.strtoupper(uniqid()),
            'name' => 'Alice',
            'status' => 'studying',
            'business_id' => $this->businessId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('edu_attendances')->insert([
            'session_id' => $id,
            'student_id' => $studentId,
            'status' => 'present',
            'checkin_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('edu_session_feedbacks')->insert([
            'session_id' => $id,
            'student_id' => $studentId,
            'rating' => 5,
            'comment' => 'Tốt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = ClassSession::with(['attendances', 'feedbacks'])->find($id);

        $this->assertCount(1, $session->attendances);
        $this->assertSame('present', $session->attendances->first()->status);
        $this->assertCount(1, $session->feedbacks);
        $this->assertSame(5, $session->feedbacks->first()->rating);

        // Cascade: deleting the session row removes its children.
        DB::table('edu_sessions')->where('id', $id)->delete();
        $this->assertSame(0, DB::table('edu_attendances')->where('session_id', $id)->count());
        $this->assertSame(0, DB::table('edu_session_feedbacks')->where('session_id', $id)->count());
    }

    // ── Audit ─────────────────────────────────────────────────────────────────

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();
        $classId = $this->makeClassId();
        $id = $this->createSession($classId)->json('data.id');

        $this->assertDatabaseHas('edu_sessions', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
