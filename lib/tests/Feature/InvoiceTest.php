<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class InvoiceTest extends TestCase
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
        $this->branchId = $this->makeBranchId($this->businessId);
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

    private function makeStudentId(): int
    {
        return DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'code' => 'STD_'.strtoupper(uniqid()),
            'name' => 'Linked Student',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function receivablePayload(array $overrides = []): array
    {
        return array_merge([
            'invoice_type' => 'receivable',
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'partner_type' => 'student',
            'items' => [
                ['name' => 'Học phí IELTS', 'quantity' => 1, 'unit_price' => 1000000],
                ['name' => 'Giáo trình', 'quantity' => 2, 'unit_price' => 250000],
            ],
            'discount' => 0,
            'tax' => 0,
        ], $overrides);
    }

    private function payablePayload(array $overrides = []): array
    {
        return array_merge([
            'invoice_type' => 'payable',
            'business_id' => $this->businessId,
            'partner_type' => 'teacher',
            'partner_id' => 1,
            'items' => [
                ['name' => 'Lương giáo viên', 'quantity' => 1, 'unit_price' => 5000000],
            ],
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/invoice/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/fin/invoice/list')->assertJsonPath('code', 403);
    }

    public function test_can_create_receivable_invoice_and_generates_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.invoice_type', 'receivable')
            ->assertJsonPath('data.status', 'pending');

        $id = $response->json('data.id');

        // subtotal = 1,000,000 + 2 * 250,000 = 1,500,000; balance = total.
        $this->assertDatabaseHas('fin_invoices', [
            'id' => $id,
            'subtotal' => 1500000,
            'total' => 1500000,
            'balance_amount' => 1500000,
            'paid_amount' => 0,
        ]);
        $this->assertDatabaseHas('fin_invoice_items', ['invoice_id' => $id, 'name' => 'Giáo trình', 'quantity' => 2]);
        $this->assertDatabaseHas('fin_invoice_histories', ['invoice_id' => $id, 'action' => 'created']);
    }

    public function test_payable_invoice_defaults_to_draft(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/invoice/create', $this->payablePayload())
            ->assertStatus(200)
            ->assertJsonPath('data.invoice_type', 'payable')
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/invoice/create', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['invoice_type', 'business_id']);
    }

    public function test_list_filters_by_type(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->assertStatus(200);
        $this->postJson('/v1/fin/invoice/create', $this->payablePayload())->assertStatus(200);

        $this->getJson('/v1/fin/invoice/list?invoice_type=payable')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.invoice_type', 'payable');
    }

    public function test_detail_returns_invoice_and_history(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $this->getJson("/v1/fin/invoice/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.invoice.id', $id)
            ->assertJsonPath('data.histories.0.action', 'created');
    }

    public function test_download_returns_a_pdf(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $response = $this->get("/v1/fin/invoice/download/{$id}");

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_receivable_payment_updates_balance_and_status(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        // Partial payment.
        $this->postJson("/v1/fin/invoice/payment/{$id}", ['amount' => 500000, 'method' => 'cash'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'partial');

        $this->assertDatabaseHas('fin_payments', ['invoice_id' => $id, 'payment_direction' => 'in', 'amount' => 500000]);

        // Settle the rest.
        $this->postJson("/v1/fin/invoice/payment/{$id}", ['amount' => 1000000, 'method' => 'transfer'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.balance_amount', '0.00');
    }

    public function test_payment_cannot_exceed_balance(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $this->postJson("/v1/fin/invoice/payment/{$id}", ['amount' => 2000000, 'method' => 'cash'])
            ->assertJsonPath('success', false);
    }

    public function test_payable_must_be_approved_before_payment(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->payablePayload())->json('data.id');

        // Draft payable rejects payment.
        $this->postJson("/v1/fin/invoice/payment/{$id}", ['amount' => 5000000, 'method' => 'transfer'])
            ->assertJsonPath('success', false);

        // Approve, then pay as a disbursement (OUT).
        $this->postJson("/v1/fin/invoice/approve/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/v1/fin/invoice/payment/{$id}", ['amount' => 5000000, 'method' => 'transfer'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('fin_payments', ['invoice_id' => $id, 'payment_direction' => 'out']);
    }

    public function test_approve_rejected_for_receivable(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $this->postJson("/v1/fin/invoice/approve/{$id}")->assertJsonPath('success', false);
    }

    public function test_cancel_invoice(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $this->postJson("/v1/fin/invoice/cancel/{$id}", ['reason' => 'Khách đổi ý'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('fin_invoice_histories', ['invoice_id' => $id, 'action' => 'cancelled']);
    }

    public function test_cancel_requires_reason(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $this->postJson("/v1/fin/invoice/cancel/{$id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_update_only_allowed_in_editable_status(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        // Pay in full -> paid -> no longer editable.
        $this->postJson("/v1/fin/invoice/payment/{$id}", ['amount' => 1500000, 'method' => 'cash'])->assertStatus(200);

        $this->putJson("/v1/fin/invoice/update/{$id}", ['note' => 'late edit'])
            ->assertJsonPath('success', false);
    }

    public function test_stamps_audit_columns(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $this->assertDatabaseHas('fin_invoices', [
            'id' => $id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    private function makeDefaultBankAccount(): void
    {
        DB::table('fin_business_bank_accounts')->insert([
            'business_id' => $this->businessId,
            'bank_name' => 'MB Bank',
            'bank_code' => '970422',
            'account_number' => '0123456789',
            'account_holder' => 'TT ANH NGU HANA',
            'is_default' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_qr_builds_vietqr_url_from_default_bank_account(): void
    {
        $this->actingAsAdmin();
        $this->makeDefaultBankAccount();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload([
            'student_id' => $this->makeStudentId(),
        ]))->json('data.id');

        $this->getJson("/v1/fin/invoice/qr/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.qr_image', fn ($url) => str_contains($url, '970422') && str_contains($url, '0123456789'))
            ->assertJsonPath('data.bank_account.bank', 'MB Bank')
            ->assertJsonPath('data.amount', 1500000);
    }

    public function test_qr_fails_when_no_bank_account_configured(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $this->getJson("/v1/fin/invoice/qr/{$id}")->assertJsonPath('success', false);
    }

    public function test_confirm_payment_logs_history_without_changing_status(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $this->postJson("/v1/fin/invoice/confirm-payment/{$id}", ['method' => 'bank_transfer', 'note' => 'Đã CK'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending_review');

        // Status stays "pending" — an admin must still confirm via /invoice/payment/{id}.
        $this->assertDatabaseHas('fin_invoices', ['id' => $id, 'status' => 'pending']);
        $this->assertDatabaseHas('fin_invoice_histories', ['invoice_id' => $id, 'action' => 'payment_reported']);
    }

    public function test_confirm_payment_requires_method(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');

        $this->postJson("/v1/fin/invoice/confirm-payment/{$id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('method');
    }

    public function test_tuition_summary_counts_unpaid_and_overdue_receivables(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->assertStatus(200);
        $overdueId = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload())->json('data.id');
        DB::table('fin_invoices')->where('id', $overdueId)->update(['due_date' => now()->subDays(3)->toDateString()]);

        $this->getJson('/v1/fin/invoice/tuition-summary')
            ->assertStatus(200)
            ->assertJsonPath('data.unpaid', 2)
            ->assertJsonPath('data.overdue', 1)
            ->assertJsonPath('data.total_due', fn ($value) => (float) $value === 3000000.0);
    }

    // ── Student/parent self-scoping (task-078 "đóng học phí") ──────────────

    /** @return array{0: User, 1: int} [acting user, edu_students id] */
    private function actingAsStudent(array $permissionCodes = ['invoice.list', 'invoice.view', 'invoice.confirm_payment']): array
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, $permissionCodes);
        $user = $this->makeUser(false, $roleId, $this->businessId);

        $studentId = DB::table('edu_students')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'user_id' => $user->id,
            'code' => 'STD_'.strtoupper(uniqid()),
            'name' => 'Acting Student',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsApi($user);

        return [$user, $studentId];
    }

    /** @return array{0: User, 1: int, 2: int[]} [acting user, crm_parents id, child student ids] */
    private function actingAsParent(array $childStudentIds, array $permissionCodes = ['invoice.list', 'invoice.view', 'invoice.confirm_payment']): array
    {
        $roleId = $this->makeRoleId($this->businessId);
        $this->grantPermissions($roleId, $permissionCodes);
        $user = $this->makeUser(false, $roleId, $this->businessId);

        $parentId = DB::table('crm_parents')->insertGetId([
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'user_id' => $user->id,
            'code' => 'PH_'.strtoupper(uniqid()),
            'name' => 'Acting Parent',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($childStudentIds as $studentId) {
            DB::table('crm_parent_student')->insert([
                'parent_id' => $parentId,
                'student_id' => $studentId,
                'relation' => 'guardian',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAsApi($user);

        return [$user, $parentId, $childStudentIds];
    }

    public function test_student_only_lists_own_invoices(): void
    {
        [$user, $ownStudentId] = $this->actingAsStudent();
        $otherStudentId = $this->makeStudentId();

        $this->actingAsAdmin($this->businessId);
        $this->postJson('/v1/fin/invoice/create', $this->receivablePayload(['student_id' => $ownStudentId]))->assertStatus(200);
        $this->postJson('/v1/fin/invoice/create', $this->receivablePayload(['student_id' => $otherStudentId]))->assertStatus(200);

        $this->actingAsApi($user);

        $this->getJson('/v1/fin/invoice/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.student.id', $ownStudentId);
    }

    public function test_student_cannot_view_another_students_invoice(): void
    {
        [$user, $ownStudentId] = $this->actingAsStudent();
        $otherStudentId = $this->makeStudentId();

        $this->actingAsAdmin($this->businessId);
        $otherInvoiceId = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload(['student_id' => $otherStudentId]))->json('data.id');

        $this->actingAsApi($user);

        $this->getJson("/v1/fin/invoice/detail/{$otherInvoiceId}")->assertJsonPath('code', 403);
        $this->getJson("/v1/fin/invoice/qr/{$otherInvoiceId}")->assertJsonPath('code', 403);
    }

    public function test_student_can_view_and_confirm_payment_for_own_invoice(): void
    {
        [$user, $ownStudentId] = $this->actingAsStudent();

        $this->actingAsAdmin($this->businessId);
        $invoiceId = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload(['student_id' => $ownStudentId]))->json('data.id');
        $this->makeDefaultBankAccount();

        $this->actingAsApi($user);

        $this->getJson("/v1/fin/invoice/detail/{$invoiceId}")
            ->assertStatus(200)
            ->assertJsonPath('data.invoice.id', $invoiceId);

        $this->getJson("/v1/fin/invoice/qr/{$invoiceId}")->assertStatus(200);

        $this->postJson("/v1/fin/invoice/confirm-payment/{$invoiceId}", ['method' => 'bank_transfer'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending_review');
    }

    public function test_student_cannot_mark_own_invoice_paid(): void
    {
        // invoice.confirm_payment (granted) must NOT imply invoice.pay
        // (withheld) — a student can't settle their own invoice for real.
        [$user, $ownStudentId] = $this->actingAsStudent();

        $this->actingAsAdmin($this->businessId);
        $invoiceId = $this->postJson('/v1/fin/invoice/create', $this->receivablePayload(['student_id' => $ownStudentId]))->json('data.id');

        $this->actingAsApi($user);

        $this->postJson("/v1/fin/invoice/payment/{$invoiceId}", ['amount' => 1500000, 'method' => 'cash'])
            ->assertJsonPath('code', 403);
    }

    public function test_parent_sees_only_childrens_invoices(): void
    {
        $childStudentId = $this->makeStudentId();
        $otherStudentId = $this->makeStudentId();
        [$user] = $this->actingAsParent([$childStudentId]);

        $this->actingAsAdmin($this->businessId);
        $this->postJson('/v1/fin/invoice/create', $this->receivablePayload(['student_id' => $childStudentId]))->assertStatus(200);
        $this->postJson('/v1/fin/invoice/create', $this->receivablePayload(['student_id' => $otherStudentId]))->assertStatus(200);

        $this->actingAsApi($user);

        $this->getJson('/v1/fin/invoice/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.student.id', $childStudentId);

        $this->getJson('/v1/fin/invoice/tuition-summary')
            ->assertStatus(200)
            ->assertJsonPath('data.unpaid', 1);
    }
}
