<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class ClassRoomTest extends TestCase
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
        $this->courseId = $this->makeCourseId();
    }

    private function makeCourseId(): int
    {
        return DB::table('edu_courses')->insertGetId([
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'IELTS Foundation - Khai giảng tháng 7',
            'code' => 'IELTS-F-'.uniqid(),
            'course_id' => $this->courseId,
            'learning_type' => 'flexible',
            'start_date' => now()->addDays(30)->toDateString(),
        ], $overrides);
    }

    private function makeClassId(): int
    {
        return $this->postJson('/v1/edu/class-room/create', $this->payload())->json('data.id');
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/class-room/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/class-room/list')->assertJsonPath('code', 403);
    }

    public function test_manager_with_permission_can_access(): void
    {
        $this->actingAsManager(['class.list']);

        $this->getJson('/v1/edu/class-room/list')->assertJsonPath('success', true);
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function test_can_create_class(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/edu/class-room/create', $this->payload(['code' => 'IELTS-F-2026-07']));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'IELTS-F-2026-07')
            ->assertJsonPath('data.status', 'upcoming');

        $this->assertDatabaseHas('edu_classes', ['code' => 'IELTS-F-2026-07', 'status' => 'upcoming']);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/class-room/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'course_id', 'learning_type', 'start_date']);
    }

    public function test_create_rejects_duplicate_code(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/class-room/create', $this->payload(['code' => 'DUPE-001']))->assertStatus(200);

        $this->postJson('/v1/edu/class-room/create', $this->payload(['code' => 'DUPE-001', 'name' => 'Other']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_create_scheduled_type_requires_schedules(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/class-room/create', $this->payload(['learning_type' => 'scheduled']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('schedules');
    }

    public function test_create_scheduled_without_teacher_is_draft(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'learning_type' => 'scheduled',
            'schedules' => [
                ['weekday' => 2, 'start_time' => '19:00', 'end_time' => '20:30'],
            ],
        ]));

        $response->assertStatus(200)->assertJsonPath('data.status', 'draft');
    }

    public function test_create_includes_schedules_in_response(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'learning_type' => 'flexible',
            'schedules' => [
                ['weekday' => 2, 'start_time' => '19:00', 'end_time' => '20:30'],
                ['weekday' => 5, 'start_time' => '19:00', 'end_time' => '20:30'],
            ],
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.schedules');
    }

    public function test_create_clones_course_curriculum_when_enabled(): void
    {
        $this->actingAsAdmin();

        DB::table('edu_course_curriculums')->insert([
            ['course_id' => $this->courseId, 'title' => 'Unit 1', 'order' => 1, 'content' => 'Intro', 'created_at' => now(), 'updated_at' => now()],
            ['course_id' => $this->courseId, 'title' => 'Unit 2', 'order' => 2, 'content' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-CURR',
            'use_course_curriculum' => true,
        ]))->assertStatus(200)->json('data.id');

        $this->assertEquals(2, DB::table('edu_class_curriculums')->where('class_id', $id)->count());
        $this->assertDatabaseHas('edu_class_curriculums', [
            'class_id' => $id,
            'title' => 'Unit 1',
            'order' => 1,
            'content' => 'Intro',
        ]);
    }

    public function test_create_skips_curriculum_clone_when_disabled(): void
    {
        $this->actingAsAdmin();

        DB::table('edu_course_curriculums')->insert([
            'course_id' => $this->courseId, 'title' => 'Unit 1', 'order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload(['code' => 'CLS-NOCURR']))
            ->assertStatus(200)->json('data.id');

        $this->assertEquals(0, DB::table('edu_class_curriculums')->where('class_id', $id)->count());
    }

    // ── Create + generate lessons ───────────────────────────────────────────────

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

    public function test_create_generates_lessons_from_plan_when_from_date_given(): void
    {
        $this->actingAsAdmin();

        $planId = $this->makePublishedPlan(3);

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-GEN',
            'learning_type' => 'scheduled',
            'lesson_plan_id' => $planId,
            'schedules' => [
                ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '10:00'],
                ['weekday' => 3, 'start_time' => '08:00', 'end_time' => '10:00'],
            ],
            'generate_from_date' => '2026-07-01',
        ]))->assertStatus(200)->json('data.id');

        $this->assertSame(3, DB::table('edu_lessons')->where('class_room_id', $id)->count());

        $first = DB::table('edu_lessons')->where('class_room_id', $id)->orderBy('lesson_no')->first();
        $this->assertSame('Lesson 1', $first->lesson_title);
        $this->assertSame('Objective 1', $first->objective);
        $this->assertSame('scheduled', $first->status);
        $this->assertDatabaseHas('edu_lesson_activities', ['lesson_id' => $first->id, 'title' => 'Warm-up 1', 'status' => 'pending']);
    }

    public function test_create_without_generate_from_date_does_not_generate_lessons(): void
    {
        $this->actingAsAdmin();

        $planId = $this->makePublishedPlan(3);

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-NOGEN',
            'learning_type' => 'scheduled',
            'lesson_plan_id' => $planId,
            'schedules' => [
                ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '10:00'],
            ],
        ]))->assertStatus(200)->json('data.id');

        $this->assertSame(0, DB::table('edu_lessons')->where('class_room_id', $id)->count());
    }

    public function test_create_generate_fails_when_plan_not_published(): void
    {
        $this->actingAsAdmin();

        $planId = DB::table('edu_lesson_plans')->insertGetId([
            'plan_code' => 'P_'.strtoupper(uniqid()),
            'plan_name' => 'Draft Plan',
            'course_id' => $this->courseId,
            'version' => 1,
            'total_lessons' => 0,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-DRAFTPLAN',
            'learning_type' => 'scheduled',
            'lesson_plan_id' => $planId,
            'schedules' => [
                ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '10:00'],
            ],
            'generate_from_date' => '2026-07-01',
        ]))->assertJsonPath('success', false);

        // The whole creation rolled back — no orphaned class left behind.
        $this->assertDatabaseMissing('edu_classes', ['code' => 'CLS-DRAFTPLAN']);
    }

    // ── List ─────────────────────────────────────────────────────────────────

    public function test_can_list_and_search_classes(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/class-room/create', $this->payload(['code' => 'CLS-001', 'name' => 'IELTS Advanced']));
        $this->postJson('/v1/edu/class-room/create', $this->payload(['code' => 'CLS-002', 'name' => 'TOEIC Intro']));

        $this->getJson('/v1/edu/class-room/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/edu/class-room/list?search=TOEIC')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.code', 'CLS-002');
    }

    public function test_list_filters_by_status(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/class-room/create', $this->payload(['code' => 'CLS-UP']));
        $id = $this->postJson('/v1/edu/class-room/create', $this->payload(['code' => 'CLS-SUS']))->json('data.id');
        $this->postJson("/v1/edu/class-room/suspend/{$id}", ['reason' => 'test']);

        $this->getJson('/v1/edu/class-room/list?status=upcoming')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);

        $this->getJson('/v1/edu/class-room/list?status=suspended')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    public function test_list_filters_by_weekday(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-MON',
            'learning_type' => 'flexible',
            'schedules' => [['weekday' => 2, 'start_time' => '19:00', 'end_time' => '20:30']],
        ]));
        $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-WED',
            'learning_type' => 'flexible',
            'schedules' => [['weekday' => 4, 'start_time' => '19:00', 'end_time' => '20:30']],
        ]));

        $this->getJson('/v1/edu/class-room/list?weekday=2')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.code', 'CLS-MON');
    }

    public function test_list_filters_by_shift(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-EVE',
            'learning_type' => 'flexible',
            'schedules' => [['weekday' => 2, 'start_time' => '19:00', 'end_time' => '20:30']],
        ]));
        $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-MORN',
            'learning_type' => 'flexible',
            'schedules' => [['weekday' => 2, 'start_time' => '08:00', 'end_time' => '09:30']],
        ]));

        $this->getJson('/v1/edu/class-room/list?shift=evening')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.code', 'CLS-EVE');

        $this->getJson('/v1/edu/class-room/list?shift=morning')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.code', 'CLS-MORN');
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function test_detail_returns_statistics(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeClassId();

        $this->getJson("/v1/edu/class-room/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.class.id', $id)
            ->assertJsonStructure([
                'data' => [
                    'class' => ['id', 'code', 'name', 'status', 'learning_type', 'schedules'],
                    'statistics' => [
                        'students' => ['total', 'active', 'reserved', 'completed', 'dropped'],
                        'operational' => ['total_sessions', 'completed_sessions', 'pending_sessions', 'completion_rate', 'avg_attendance_rate'],
                        'financial' => ['total_revenue', 'recognized_revenue', 'debt', 'refunds'],
                    ],
                ],
            ]);
    }

    public function test_detail_statistics_reflect_enrollments_and_sessions(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-STATS',
            'min_warning_capacity' => 5,
            'min_capacity' => 8,
            'max_warning_capacity' => 18,
            'max_capacity' => 20,
        ]))->assertStatus(200)->json('data.id');

        foreach (['active', 'active', 'reserved', 'dropped'] as $i => $status) {
            $studentId = DB::table('edu_students')->insertGetId([
                'code' => 'STD_'.strtoupper(uniqid()).$i,
                'name' => 'Student '.$i,
                'status' => 'studying',
                'business_id' => $this->businessId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('edu_class_students')->insert([
                'class_id' => $id,
                'student_id' => $studentId,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('edu_sessions')->insert([
            ['business_id' => $this->businessId, 'class_id' => $id, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => $this->businessId, 'class_id' => $id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->getJson("/v1/edu/class-room/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.statistics.students.total', 4)
            ->assertJsonPath('data.statistics.students.active', 2)
            ->assertJsonPath('data.statistics.students.reserved', 1)
            ->assertJsonPath('data.statistics.students.dropped', 1)
            ->assertJsonPath('data.statistics.operational.total_sessions', 2)
            ->assertJsonPath('data.statistics.operational.completed_sessions', 1)
            ->assertJsonPath('data.statistics.operational.pending_sessions', 1)
            // current_students (active=2) <= min_warning (5) ⇒ low badge (spec §8).
            ->assertJsonPath('data.class.capacity_warning', 'low');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_can_update_class(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeClassId();

        $this->putJson("/v1/edu/class-room/update/{$id}", [
            'name' => 'Renamed Class',
            'description' => 'Updated description.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Renamed Class');

        $this->assertDatabaseHas('edu_classes', ['id' => $id, 'name' => 'Renamed Class']);
    }

    public function test_update_replaces_schedules(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'schedules' => [['weekday' => 2, 'start_time' => '19:00', 'end_time' => '20:30']],
        ]))->json('data.id');

        $this->putJson("/v1/edu/class-room/update/{$id}", [
            'schedules' => [
                ['weekday' => 3, 'start_time' => '18:00', 'end_time' => '19:30'],
                ['weekday' => 6, 'start_time' => '08:00', 'end_time' => '09:30'],
            ],
        ])
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.schedules');

        $this->assertEquals(2, DB::table('edu_class_schedules')->where('class_id', $id)->count());
    }

    public function test_update_generates_lessons_when_attaching_plan_for_the_first_time(): void
    {
        $this->actingAsAdmin();

        $planId = $this->makePublishedPlan(2);

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-ATTACH',
            'learning_type' => 'scheduled',
            'schedules' => [
                ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '10:00'],
            ],
        ]))->json('data.id');

        $this->putJson("/v1/edu/class-room/update/{$id}", [
            'lesson_plan_id' => $planId,
            'generate_from_date' => '2026-07-01',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lesson_plan_id', $planId);

        $this->assertSame(2, DB::table('edu_lessons')->where('class_room_id', $id)->count());
    }

    public function test_update_does_not_regenerate_when_class_already_has_a_plan(): void
    {
        $this->actingAsAdmin();

        $planId = $this->makePublishedPlan(2);
        $otherPlanId = $this->makePublishedPlan(3);

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-ALREADY',
            'learning_type' => 'scheduled',
            'lesson_plan_id' => $planId,
            'schedules' => [
                ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '10:00'],
            ],
            'generate_from_date' => '2026-07-01',
        ]))->json('data.id');

        $this->assertSame(2, DB::table('edu_lessons')->where('class_room_id', $id)->count());

        // Class already had a plan before this update — swapping the plan
        // must not implicitly regenerate lessons from the new one.
        $this->putJson("/v1/edu/class-room/update/{$id}", [
            'lesson_plan_id' => $otherPlanId,
            'generate_from_date' => '2026-08-01',
        ])->assertStatus(200);

        $this->assertSame(2, DB::table('edu_lessons')->where('class_room_id', $id)->count());
        $this->assertDatabaseHas('edu_classes', ['id' => $id, 'lesson_plan_id' => $otherPlanId]);
    }

    public function test_update_without_generate_from_date_does_not_generate_lessons(): void
    {
        $this->actingAsAdmin();

        $planId = $this->makePublishedPlan(2);

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-NOFLAG',
            'learning_type' => 'scheduled',
            'schedules' => [
                ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '10:00'],
            ],
        ]))->json('data.id');

        $this->putJson("/v1/edu/class-room/update/{$id}", ['lesson_plan_id' => $planId])
            ->assertStatus(200);

        $this->assertSame(0, DB::table('edu_lessons')->where('class_room_id', $id)->count());
    }

    public function test_update_generate_fails_when_plan_not_published(): void
    {
        $this->actingAsAdmin();

        $draftPlanId = DB::table('edu_lesson_plans')->insertGetId([
            'plan_code' => 'P_'.strtoupper(uniqid()),
            'plan_name' => 'Draft Plan',
            'course_id' => $this->courseId,
            'version' => 1,
            'total_lessons' => 0,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload([
            'code' => 'CLS-DRAFTPLAN-UPD',
            'learning_type' => 'scheduled',
            'schedules' => [
                ['weekday' => 1, 'start_time' => '08:00', 'end_time' => '10:00'],
            ],
        ]))->json('data.id');

        $this->putJson("/v1/edu/class-room/update/{$id}", [
            'lesson_plan_id' => $draftPlanId,
            'generate_from_date' => '2026-07-01',
        ])->assertJsonPath('success', false);

        $this->assertDatabaseHas('edu_classes', ['id' => $id, 'lesson_plan_id' => null]);
    }

    public function test_update_code_and_status_are_immutable(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/edu/class-room/create', $this->payload(['code' => 'ORIGINAL']))->json('data.id');

        $this->putJson("/v1/edu/class-room/update/{$id}", ['code' => 'CHANGED', 'status' => 'completed'])
            ->assertStatus(200);

        $this->assertDatabaseHas('edu_classes', ['id' => $id, 'code' => 'ORIGINAL', 'status' => 'upcoming']);
    }

    // ── Suspend / Restore ─────────────────────────────────────────────────────

    public function test_suspend_and_restore_lifecycle(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeClassId();

        $this->postJson("/v1/edu/class-room/suspend/{$id}", ['reason' => 'Giáo viên nghỉ bệnh'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('edu_classes', ['id' => $id, 'status' => 'suspended']);

        // Suspending again is rejected.
        $this->postJson("/v1/edu/class-room/suspend/{$id}", ['reason' => 'again'])
            ->assertJsonPath('success', false);

        $this->postJson("/v1/edu/class-room/restore/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'upcoming');

        $this->assertDatabaseHas('edu_classes', ['id' => $id, 'status' => 'upcoming']);
    }

    public function test_suspend_requires_reason(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeClassId();

        $this->postJson("/v1/edu/class-room/suspend/{$id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_restore_rejected_when_not_suspended(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeClassId();

        $this->postJson("/v1/edu/class-room/restore/{$id}")
            ->assertJsonPath('success', false);
    }

    public function test_completed_class_cannot_be_suspended(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeClassId();

        DB::table('edu_classes')->where('id', $id)->update(['status' => 'completed']);

        $this->postJson("/v1/edu/class-room/suspend/{$id}", ['reason' => 'force'])
            ->assertJsonPath('success', false);
    }

    // ── Audit ─────────────────────────────────────────────────────────────────

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->makeClassId();

        $this->assertDatabaseHas('edu_classes', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
