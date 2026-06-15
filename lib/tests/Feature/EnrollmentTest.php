<?php

namespace Tests\Feature;

use Database\Seeders\EnrollmentPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $courseId;

    private int $classId;

    private int $studentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EnrollmentPermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = $this->makeCourseId();
        $this->classId = $this->makeClassId($this->courseId);
        $this->studentId = $this->makeStudentId();
    }

    private function makeCourseId(bool $isActive = true): int
    {
        return DB::table('edu_courses')->insertGetId([
            'name' => 'IELTS Foundation',
            'code' => 'IELTS_F_'.uniqid(),
            'duration_minutes' => 90,
            'price_per_lesson' => 250000,
            'is_active' => $isActive,
            'business_id' => $this->businessId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassId(int $courseId, array $overrides = []): int
    {
        return DB::table('edu_classes')->insertGetId(array_merge([
            'course_id' => $courseId,
            'business_id' => $this->businessId,
            'code' => 'CLS-'.strtoupper(uniqid()),
            'name' => 'Class '.uniqid(),
            'learning_type' => 'flexible',
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'max_capacity' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function makeStudentId(array $overrides = []): int
    {
        return DB::table('edu_students')->insertGetId(array_merge([
            'code' => 'STD_'.strtoupper(uniqid()),
            'name' => 'Nguyễn Văn Học',
            'phone' => '0900'.random_int(100000, 999999),
            'status' => 'studying',
            'business_id' => $this->businessId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'student_id' => $this->studentId,
            'course_id' => $this->courseId,
            'class_id' => $this->classId,
            'total_lessons' => 24,
            'price_per_lesson' => 250000,
        ], $overrides);
    }

    private function makeEnrollmentId(array $overrides = []): int
    {
        return $this->postJson('/v1/edu/enrollment/create', $this->payload($overrides))->json('data.id');
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/edu/enrollment/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/edu/enrollment/list')->assertJsonPath('code', 403);
    }

    public function test_manager_with_permission_can_access(): void
    {
        $this->actingAsManager(['enrollment.list']);

        $this->getJson('/v1/edu/enrollment/list')->assertJsonPath('success', true);
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function test_can_create_enrollment_with_side_effects(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/edu/enrollment/create', $this->payload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'studying')
            ->assertJsonPath('data.total_lessons', 24)
            ->assertJsonPath('data.remaining_lessons', 24)
            ->assertJsonPath('data.tuition_amount', 6000000);

        $id = $response->json('data.id');

        $this->assertDatabaseHas('edu_enrollments', ['id' => $id, 'status' => 'studying']);
        $this->assertDatabaseHas('edu_class_students', [
            'class_id' => $this->classId,
            'student_id' => $this->studentId,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('fin_invoices', ['enrollment_id' => $id, 'total' => 6000000]);
    }

    public function test_create_computes_discount_and_debt(): void
    {
        $this->actingAsAdmin();

        // 24 × 250000 = 6,000,000 ; 10% + 0 = 600,000 ; payable 5,400,000 ; paid 3,000,000 ⇒ debt 2,400,000.
        $response = $this->postJson('/v1/edu/enrollment/create', $this->payload([
            'discount_percent' => 10,
            'paid_amount' => 3000000,
            'bonus_lessons' => 2,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('data.discount_amount', 600000)
            ->assertJsonPath('data.paid_amount', 3000000)
            ->assertJsonPath('data.debt_amount', 2400000)
            ->assertJsonPath('data.remaining_lessons', 26);

        $id = $response->json('data.id');

        $this->assertDatabaseHas('fin_payments', ['enrollment_id' => $id, 'amount' => 3000000]);
        // Debt is the invoice's outstanding balance (debt.md BR-10), not a fin_debts row.
        $this->assertDatabaseHas('fin_invoices', ['enrollment_id' => $id, 'balance_amount' => 2400000, 'status' => 'partial']);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/enrollment/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['student_id', 'course_id', 'class_id', 'total_lessons', 'price_per_lesson']);
    }

    public function test_create_rejects_class_not_in_course(): void
    {
        $this->actingAsAdmin();

        $otherCourse = $this->makeCourseId();
        $otherClass = $this->makeClassId($otherCourse);

        $this->postJson('/v1/edu/enrollment/create', $this->payload(['class_id' => $otherClass]))
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_create_rejects_inactive_course(): void
    {
        $this->actingAsAdmin();

        $course = $this->makeCourseId(isActive: false);
        $class = $this->makeClassId($course);

        $this->postJson('/v1/edu/enrollment/create', $this->payload(['course_id' => $course, 'class_id' => $class]))
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_create_rejects_duplicate_active_enrollment(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/edu/enrollment/create', $this->payload())->assertJsonPath('success', true);

        $this->postJson('/v1/edu/enrollment/create', $this->payload())
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_create_rejects_when_class_is_full(): void
    {
        $this->actingAsAdmin();

        $class = $this->makeClassId($this->courseId, ['max_capacity' => 1]);

        $this->postJson('/v1/edu/enrollment/create', $this->payload(['class_id' => $class]))
            ->assertJsonPath('success', true);

        $other = $this->makeStudentId();

        $this->postJson('/v1/edu/enrollment/create', $this->payload(['class_id' => $class, 'student_id' => $other]))
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    // ── List ─────────────────────────────────────────────────────────────────

    public function test_list_filters_by_status_and_debt(): void
    {
        $this->actingAsAdmin();

        $paidId = $this->makeEnrollmentId(['paid_amount' => 6000000]);
        $debtId = $this->makeEnrollmentId(['student_id' => $this->makeStudentId()]);

        $this->getJson('/v1/edu/enrollment/list?status=studying')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 2);

        $this->getJson('/v1/edu/enrollment/list?has_debt=true')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $debtId);
    }

    public function test_list_searches_by_student_phone(): void
    {
        $this->actingAsAdmin();

        $student = $this->makeStudentId(['phone' => '0911222333']);
        $this->makeEnrollmentId(['student_id' => $student]);
        $this->makeEnrollmentId(['student_id' => $this->makeStudentId(['phone' => '0888000111'])]);

        $this->getJson('/v1/edu/enrollment/list?search=0911222333')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1);
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function test_detail_returns_progress_and_financial(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeEnrollmentId(['paid_amount' => 1000000]);

        $this->getJson("/v1/edu/enrollment/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.enrollment.id', $id)
            ->assertJsonStructure([
                'data' => [
                    'enrollment' => ['id', 'code', 'status'],
                    'progress' => ['total_lessons', 'completed_lessons', 'remaining_lessons', 'completion_rate'],
                    'financial' => ['tuition_amount', 'discount_amount', 'paid_amount', 'debt_amount', 'refund_amount'],
                    'payments',
                    'transfers',
                    'suspensions',
                ],
            ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_can_update_note(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeEnrollmentId();

        $this->putJson("/v1/edu/enrollment/update/{$id}", ['note' => 'Đã liên hệ phụ huynh.'])
            ->assertStatus(200)
            ->assertJsonPath('data.note', 'Đã liên hệ phụ huynh.');
    }

    // ── Suspend ───────────────────────────────────────────────────────────────

    public function test_suspend_lifecycle(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeEnrollmentId();

        $this->postJson("/v1/edu/enrollment/suspend/{$id}", [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'reason' => 'Học viên đi công tác.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        $this->assertDatabaseHas('edu_enrollment_suspensions', ['enrollment_id' => $id]);

        // Suspending again is rejected.
        $this->postJson("/v1/edu/enrollment/suspend/{$id}", [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'reason' => 'again',
        ])->assertJsonPath('success', false);
    }

    public function test_suspend_requires_dates_and_reason(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeEnrollmentId();

        $this->postJson("/v1/edu/enrollment/suspend/{$id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date', 'reason']);
    }

    public function test_suspend_rejected_when_no_lessons_remaining(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeEnrollmentId();
        DB::table('edu_enrollments')->where('id', $id)->update(['remaining_lessons' => 0]);

        $this->postJson("/v1/edu/enrollment/suspend/{$id}", [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'reason' => 'test',
        ])->assertJsonPath('success', false);
    }

    // ── Transfer ──────────────────────────────────────────────────────────────

    public function test_transfer_to_same_course_class(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeEnrollmentId();
        $target = $this->makeClassId($this->courseId);

        $this->postJson("/v1/edu/enrollment/transfer/{$id}", [
            'to_class_id' => $target,
            'transfer_date' => now()->toDateString(),
            'reason' => 'Đổi lịch.',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.class_id', $target);

        $this->assertDatabaseHas('edu_enrollment_transfers', [
            'enrollment_id' => $id,
            'from_class_id' => $this->classId,
            'to_class_id' => $target,
        ]);
        $this->assertDatabaseHas('edu_class_students', [
            'class_id' => $this->classId,
            'student_id' => $this->studentId,
            'status' => 'transferred_out',
        ]);
        $this->assertDatabaseHas('edu_class_students', [
            'class_id' => $target,
            'student_id' => $this->studentId,
            'status' => 'active',
        ]);
    }

    public function test_transfer_rejects_different_course(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeEnrollmentId();
        $otherClass = $this->makeClassId($this->makeCourseId());

        $this->postJson("/v1/edu/enrollment/transfer/{$id}", [
            'to_class_id' => $otherClass,
            'transfer_date' => now()->toDateString(),
        ])->assertJsonPath('success', false);
    }

    // ── Refund ────────────────────────────────────────────────────────────────

    public function test_refund_closes_enrollment(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeEnrollmentId(['paid_amount' => 6000000]);

        $this->postJson("/v1/edu/enrollment/refund/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'refunded')
            ->assertJsonPath('data.remaining_lessons', 0);

        $invoiceId = DB::table('fin_invoices')->where('enrollment_id', $id)->value('id');
        $this->assertDatabaseHas('fin_refunds', ['invoice_id' => $invoiceId, 'amount' => 6000000]);
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function test_cancel_removes_student_from_class(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeEnrollmentId();

        $this->postJson("/v1/edu/enrollment/cancel/{$id}", ['reason' => 'Không tiếp tục.'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('edu_class_students', [
            'class_id' => $this->classId,
            'student_id' => $this->studentId,
            'status' => 'dropped',
        ]);
    }

    // ── Audit ─────────────────────────────────────────────────────────────────

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->makeEnrollmentId();

        $this->assertDatabaseHas('edu_enrollments', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
