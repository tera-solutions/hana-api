<?php

namespace Tests\Feature;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class StudentDebtStatusTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->businessId = $this->makeBusinessId();
        $this->branchId = DB::table('sys_branches')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Branch '.uniqid(),
            'code' => 'CN_'.strtoupper(uniqid()),
            'address' => '123 Le Loi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeStudentId(string $status = 'active'): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'STD_'.strtoupper(uniqid()),
            'name' => 'Student '.uniqid(),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeOverdueInvoice(int $studentId, string $status = 'pending'): int
    {
        return DB::table('fin_invoices')->insertGetId([
            'code' => 'INV_'.strtoupper(uniqid()),
            'invoice_type' => 'receivable',
            'status' => $status,
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'partner_type' => 'student',
            'partner_id' => $studentId,
            'student_id' => $studentId,
            'invoice_date' => now()->subDays(40)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'subtotal' => 1000000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000000,
            'paid_amount' => 0,
            'balance_amount' => 1000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_command_flips_active_student_with_overdue_invoice_to_debt(): void
    {
        $studentId = $this->makeStudentId('active');
        $this->makeOverdueInvoice($studentId);

        $this->artisan('students:sync-debt-status')->assertExitCode(0);

        $this->assertDatabaseHas('edu_students', ['id' => $studentId, 'status' => 'debt']);
    }

    public function test_command_leaves_non_overdue_student_active(): void
    {
        $studentId = $this->makeStudentId('active');
        DB::table('fin_invoices')->insert([
            'code' => 'INV_'.strtoupper(uniqid()),
            'invoice_type' => 'receivable',
            'status' => 'pending',
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'partner_type' => 'student',
            'partner_id' => $studentId,
            'student_id' => $studentId,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => 1000000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000000,
            'paid_amount' => 0,
            'balance_amount' => 1000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('students:sync-debt-status');

        $this->assertDatabaseHas('edu_students', ['id' => $studentId, 'status' => 'active']);
    }

    public function test_command_never_touches_suspended_student(): void
    {
        $studentId = $this->makeStudentId('suspended');
        $this->makeOverdueInvoice($studentId);

        $this->artisan('students:sync-debt-status');

        $this->assertDatabaseHas('edu_students', ['id' => $studentId, 'status' => 'suspended']);
    }

    public function test_paying_off_overdue_invoice_reverts_student_to_active(): void
    {
        $this->actingAsAdmin();

        $studentId = $this->makeStudentId('active');
        $invoiceId = $this->makeOverdueInvoice($studentId);

        $this->artisan('students:sync-debt-status');
        $this->assertDatabaseHas('edu_students', ['id' => $studentId, 'status' => 'debt']);

        $this->postJson("/v1/fin/invoice/payment/{$invoiceId}", ['amount' => 1000000, 'method' => 'cash'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('edu_students', ['id' => $studentId, 'status' => 'active']);
    }

    private function configureUnpaidStatus(string $status): void
    {
        DB::table('fin_invoice_configs')->insert([
            'business_id' => $this->businessId,
            'auto_generate' => false,
            'billing_day' => 1,
            'due_days' => 7,
            'unpaid_student_status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_command_flips_to_configured_suspended_status_instead_of_debt(): void
    {
        $this->configureUnpaidStatus('suspended');

        $studentId = $this->makeStudentId('active');
        $this->makeOverdueInvoice($studentId);

        $this->artisan('students:sync-debt-status')->assertExitCode(0);

        $this->assertDatabaseHas('edu_students', ['id' => $studentId, 'status' => 'suspended']);
    }

    public function test_configured_suspended_target_reverts_to_active_once_paid(): void
    {
        $this->actingAsAdmin($this->businessId);
        $this->configureUnpaidStatus('suspended');

        $studentId = $this->makeStudentId('active');
        $invoiceId = $this->makeOverdueInvoice($studentId);

        $this->artisan('students:sync-debt-status');
        $this->assertDatabaseHas('edu_students', ['id' => $studentId, 'status' => 'suspended']);

        $this->postJson("/v1/fin/invoice/payment/{$invoiceId}", ['amount' => 1000000, 'method' => 'cash'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('edu_students', ['id' => $studentId, 'status' => 'active']);
    }

    public function test_configured_suspended_target_never_touches_student_in_debt_status(): void
    {
        // A "debt" status here means something else (no config for this
        // business ever set it that way) — with unpaid_student_status =
        // suspended, "debt" isn't in the managed [active, suspended] pair.
        $this->configureUnpaidStatus('suspended');

        $studentId = $this->makeStudentId('debt');
        $this->makeOverdueInvoice($studentId);

        $this->artisan('students:sync-debt-status');

        $this->assertDatabaseHas('edu_students', ['id' => $studentId, 'status' => 'debt']);
    }
}
