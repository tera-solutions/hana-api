<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class ClassScheduleTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;
    private int $courseId;
    private int $classId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = $this->makeCourseId();
        $this->classId = $this->makeClassId();
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
            'name' => 'IELTS Foundation',
            'code' => 'IELTS_F_' . uniqid(),
            'duration_minutes' => 90,
            'price_per_lesson' => 250000,
            'is_active' => true,
            'business_id' => $this->businessId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassId(): int
    {
        return DB::table('edu_classes')->insertGetId([
            'code' => 'CLS_' . strtoupper(uniqid()),
            'name' => 'Test Schedule Class',
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'learning_type' => 'flexible',
            'status' => 'upcoming',
            'start_date' => now()->addDays(30)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'weekday' => 2,
            'start_time' => '19:00',
            'end_time' => '20:30',
        ], $overrides);
    }

    private function addSchedule(array $overrides = []): int
    {
        return $this->postJson("/v1/edu/class-room/{$this->classId}/schedule/create", $this->payload($overrides))
            ->json('data.id');
    }

    public function test_can_list_schedules(): void
    {
        $this->actingAsAdmin();

        $this->addSchedule(['weekday' => 2]);
        $this->addSchedule(['weekday' => 5]);

        $this->getJson("/v1/edu/class-room/{$this->classId}/schedule/list")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_add_schedule(): void
    {
        $this->actingAsAdmin();

        $this->postJson("/v1/edu/class-room/{$this->classId}/schedule/create", $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('data.weekday', 2)
            ->assertJsonPath('data.class_id', $this->classId);

        $this->assertDatabaseHas('edu_class_schedules', ['class_id' => $this->classId, 'weekday' => 2]);
    }

    public function test_schedule_add_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson("/v1/edu/class-room/{$this->classId}/schedule/create", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['weekday', 'start_time', 'end_time']);
    }

    public function test_schedule_detail(): void
    {
        $this->actingAsAdmin();

        $scheduleId = $this->addSchedule();

        $this->getJson("/v1/edu/class-schedule/detail/{$scheduleId}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $scheduleId)
            ->assertJsonPath('data.weekday', 2);
    }

    public function test_can_update_schedule(): void
    {
        $this->actingAsAdmin();

        $scheduleId = $this->addSchedule();

        $this->putJson("/v1/edu/class-schedule/update/{$scheduleId}", [
            'weekday' => 4,
            'start_time' => '18:30',
            'end_time' => '20:00',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.weekday', 4);

        $this->assertDatabaseHas('edu_class_schedules', ['id' => $scheduleId, 'weekday' => 4]);
    }

    public function test_can_partially_update_schedule(): void
    {
        $this->actingAsAdmin();

        $scheduleId = $this->addSchedule(['weekday' => 2, 'start_time' => '19:00', 'end_time' => '20:30']);

        // Sending only weekday must not trip the start/end cross-field check.
        $this->putJson("/v1/edu/class-schedule/update/{$scheduleId}", ['weekday' => 6])
            ->assertStatus(200)
            ->assertJsonPath('data.weekday', 6);

        $this->assertDatabaseHas('edu_class_schedules', [
            'id' => $scheduleId,
            'weekday' => 6,
            'start_time' => '19:00',
            'end_time' => '20:30',
        ]);
    }

    public function test_update_rejects_end_time_before_start_time(): void
    {
        $this->actingAsAdmin();

        $scheduleId = $this->addSchedule();

        $this->putJson("/v1/edu/class-schedule/update/{$scheduleId}", [
            'start_time' => '20:00',
            'end_time' => '19:00',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_time');
    }

    public function test_can_delete_schedule(): void
    {
        $this->actingAsAdmin();

        $scheduleId = $this->addSchedule();

        $this->deleteJson("/v1/edu/class-schedule/delete/{$scheduleId}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('edu_class_schedules', ['id' => $scheduleId]);
    }
}
