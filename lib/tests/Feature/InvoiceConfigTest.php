<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class InvoiceConfigTest extends TestCase
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

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->courseId = DB::table('edu_courses')->insertGetId([
            'name' => 'IELTS Foundation',
            'code' => 'IELTS_'.uniqid(),
            'duration_minutes' => 90,
            'price_per_lesson' => 250000,
            'is_active' => true,
            'business_id' => $this->businessId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->classId = DB::table('edu_classes')->insertGetId([
            'course_id' => $this->courseId,
            'business_id' => $this->businessId,
            'code' => 'CLS-'.strtoupper(uniqid()),
            'name' => 'Class '.uniqid(),
            'learning_type' => 'flexible',
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'max_capacity' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->studentId = DB::table('edu_students')->insertGetId([
            'code' => 'STD_'.strtoupper(uniqid()),
            'name' => 'Nguyễn Văn Học',
            'status' => 'active',
            'business_id' => $this->businessId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeSession(string $date, string $status = 'upcoming'): void
    {
        DB::table('edu_sessions')->insert([
            'business_id' => $this->businessId,
            'class_id' => $this->classId,
            'session_no' => random_int(1, 1000),
            'code' => 'SES_'.strtoupper(uniqid()),
            'name' => 'Session',
            'session_date' => $date,
            'start_time' => '18:00:00',
            'end_time' => '19:30:00',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeEnrollment(float $pricePerLesson = 250000): int
    {
        return DB::table('edu_enrollments')->insertGetId([
            'code' => 'ENR_'.strtoupper(uniqid()),
            'student_id' => $this->studentId,
            'course_id' => $this->courseId,
            'class_id' => $this->classId,
            'status' => 'studying',
            'total_lessons' => 24,
            'price_per_lesson' => $pricePerLesson,
            'enrolled_at' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ── Config CRUD ──────────────────────────────────────────────────────────

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/invoice-config')->assertJsonPath('code', 401);
    }

    public function test_get_defaults_when_unset(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->getJson('/v1/fin/invoice-config')
            ->assertStatus(200)
            ->assertJsonPath('data.auto_generate', false)
            ->assertJsonPath('data.billing_day', 1)
            ->assertJsonPath('data.due_days', 7);
    }

    public function test_can_update_config(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->putJson('/v1/fin/invoice-config', ['auto_generate' => true, 'billing_day' => 5, 'due_days' => 10])
            ->assertStatus(200)
            ->assertJsonPath('data.auto_generate', true)
            ->assertJsonPath('data.billing_day', 5);

        $this->assertDatabaseHas('fin_invoice_configs', [
            'business_id' => $this->businessId,
            'auto_generate' => true,
            'billing_day' => 5,
            'due_days' => 10,
        ]);
    }

    public function test_update_validates_billing_day_range(): void
    {
        $this->actingAsAdmin($this->businessId);

        $this->putJson('/v1/fin/invoice-config', ['auto_generate' => true, 'billing_day' => 31, 'due_days' => 7])
            ->assertStatus(422)
            ->assertJsonValidationErrors('billing_day');
    }

    // ── Recurring generation ─────────────────────────────────────────────────

    public function test_command_bills_studying_enrollment_with_sessions_this_month(): void
    {
        $today = now()->startOfMonth()->addDay(); // day 2, safely <= 28
        DB::table('fin_invoice_configs')->insert([
            'business_id' => $this->businessId,
            'auto_generate' => true,
            'billing_day' => $today->day,
            'due_days' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->makeEnrollment(250000);
        $this->makeSession($today->copy()->toDateString());
        $this->makeSession($today->copy()->addDays(2)->toDateString());

        $this->travelTo($today);
        $this->artisan('invoices:generate-recurring')->assertExitCode(0);

        $this->assertDatabaseHas('fin_invoices', [
            'student_id' => $this->studentId,
            'invoice_type' => 'receivable',
            'total' => 500000, // 2 sessions * 250,000
        ]);
    }

    public function test_command_is_idempotent_within_the_same_month(): void
    {
        $today = now()->startOfMonth()->addDay();
        DB::table('fin_invoice_configs')->insert([
            'business_id' => $this->businessId,
            'auto_generate' => true,
            'billing_day' => $today->day,
            'due_days' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->makeEnrollment();
        $this->makeSession($today->copy()->toDateString());

        $this->travelTo($today);
        $this->artisan('invoices:generate-recurring');
        $this->artisan('invoices:generate-recurring');

        $this->assertSame(1, DB::table('fin_invoices')->where('student_id', $this->studentId)->count());
    }

    public function test_command_skips_when_billing_day_does_not_match_today(): void
    {
        DB::table('fin_invoice_configs')->insert([
            'business_id' => $this->businessId,
            'auto_generate' => true,
            'billing_day' => 15,
            'due_days' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->makeEnrollment();
        $this->makeSession(now()->toDateString());

        $this->travelTo(now()->startOfMonth()->addDay()); // day 2, not 15

        $this->artisan('invoices:generate-recurring');

        $this->assertSame(0, DB::table('fin_invoices')->where('student_id', $this->studentId)->count());
    }

    public function test_command_skips_enrollment_with_no_sessions_this_month(): void
    {
        $today = now()->startOfMonth()->addDay();
        DB::table('fin_invoice_configs')->insert([
            'business_id' => $this->businessId,
            'auto_generate' => true,
            'billing_day' => $today->day,
            'due_days' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->makeEnrollment();
        // No sessions created.

        $this->travelTo($today);
        $this->artisan('invoices:generate-recurring');

        $this->assertSame(0, DB::table('fin_invoices')->where('student_id', $this->studentId)->count());
    }

    public function test_command_skips_business_with_auto_generate_disabled(): void
    {
        $today = now()->startOfMonth()->addDay();
        DB::table('fin_invoice_configs')->insert([
            'business_id' => $this->businessId,
            'auto_generate' => false,
            'billing_day' => $today->day,
            'due_days' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->makeEnrollment();
        $this->makeSession($today->copy()->toDateString());

        $this->travelTo($today);
        $this->artisan('invoices:generate-recurring');

        $this->assertSame(0, DB::table('fin_invoices')->where('student_id', $this->studentId)->count());
    }
}
