<?php

namespace Tests\Feature;

use Database\Seeders\DebtPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

class DebtTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private int $businessId;

    private int $branchId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DebtPermissionSeeder::class);

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

    private function makeReceivableInvoice(string $dueDate = '2026-12-31', int $unitPrice = 1500000): int
    {
        return $this->postJson('/v1/fin/invoice/create', [
            'invoice_type' => 'receivable',
            'business_id' => $this->businessId,
            'branch_id' => $this->branchId,
            'partner_type' => 'student',
            'partner_id' => 1,
            'due_date' => $dueDate,
            'items' => [['name' => 'Học phí', 'quantity' => 1, 'unit_price' => $unitPrice]],
        ])->json('data.id');
    }

    private function makeApprovedPayableInvoice(int $unitPrice = 5000000): int
    {
        $id = $this->postJson('/v1/fin/invoice/create', [
            'invoice_type' => 'payable',
            'business_id' => $this->businessId,
            'partner_type' => 'teacher',
            'partner_id' => 1,
            'items' => [['name' => 'Lương', 'quantity' => 1, 'unit_price' => $unitPrice]],
        ])->json('data.id');

        $this->postJson("/v1/fin/invoice/approve/{$id}")->assertStatus(200);

        return $id;
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/v1/fin/debt/list')->assertJsonPath('code', 401);
    }

    public function test_manager_without_permission_is_forbidden(): void
    {
        $this->actingAsManager([]);

        $this->getJson('/v1/fin/debt/list')->assertJsonPath('code', 403);
    }

    public function test_list_shows_outstanding_invoices(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeReceivableInvoice();

        $this->getJson('/v1/fin/debt/list')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.invoice_id', $id)
            ->assertJsonPath('data.items.0.outstanding', '1500000.00')
            ->assertJsonPath('data.items.0.debt_status', 'current');
    }

    public function test_list_filters_overdue(): void
    {
        $this->actingAsAdmin();

        $this->makeReceivableInvoice('2026-12-31');     // current
        $this->makeReceivableInvoice('2020-01-01');     // overdue

        $this->getJson('/v1/fin/debt/list?status=overdue')
            ->assertStatus(200)
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.debt_status', 'overdue');
    }

    public function test_detail_returns_invoice_payments_and_adjustments(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeReceivableInvoice();

        $this->getJson("/v1/fin/debt/detail/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.debt.invoice_id', $id)
            ->assertJsonPath('data.outstanding', 1500000)
            ->assertJsonPath('data.adjustments', []);
    }

    public function test_aging_report_buckets(): void
    {
        $this->actingAsAdmin();

        $this->makeReceivableInvoice('2026-12-31', 1000000); // current
        $this->makeReceivableInvoice('2020-01-01', 2000000); // >90 days overdue

        $data = $this->getJson('/v1/fin/debt/aging')->assertStatus(200)->json('data');

        $this->assertEquals(1000000, $data['current']);
        $this->assertEquals(2000000, $data['overdue_90_plus']);
        $this->assertEquals(3000000, $data['total']);
    }

    public function test_dashboard_totals(): void
    {
        $this->actingAsAdmin();

        $this->makeReceivableInvoice('2026-12-31', 1500000);
        $this->makeApprovedPayableInvoice(5000000);

        $data = $this->getJson('/v1/fin/debt/dashboard')->assertStatus(200)->json('data');

        $this->assertEquals(1500000, $data['total_receivable']);
        $this->assertEquals(5000000, $data['total_payable']);
    }

    public function test_adjust_discount_reduces_invoice(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeReceivableInvoice('2026-12-31', 1500000);

        $this->postJson("/v1/fin/debt/adjust/{$id}", [
            'adjustment_type' => 'discount',
            'amount' => 500000,
            'reason' => 'Giảm giá học viên cũ',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.debt.outstanding', '1000000.00');

        $this->assertDatabaseHas('fin_debt_adjustments', ['invoice_id' => $id, 'adjustment_type' => 'discount', 'status' => 'applied']);
        $this->assertDatabaseHas('fin_invoices', ['id' => $id, 'total' => 1000000, 'balance_amount' => 1000000]);
    }

    public function test_writeoff_requires_approval(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeReceivableInvoice('2020-01-01', 1000000);

        $adjId = $this->postJson("/v1/fin/debt/writeoff/{$id}", ['reason' => 'Học viên bỏ học'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending')
            ->json('data.id');

        // Still outstanding until approved.
        $this->assertDatabaseHas('fin_invoices', ['id' => $id, 'balance_amount' => 1000000]);

        $this->postJson("/v1/fin/debt/writeoff/approve/{$adjId}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        // Debt is written off: balance 0, invoice closed.
        $this->assertDatabaseHas('fin_invoices', ['id' => $id, 'balance_amount' => 0, 'status' => 'closed']);
    }

    public function test_writeoff_can_be_denied(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeReceivableInvoice('2020-01-01', 1000000);
        $adjId = $this->postJson("/v1/fin/debt/writeoff/{$id}", ['reason' => 'x'])->json('data.id');

        $this->postJson("/v1/fin/debt/writeoff/deny/{$adjId}", ['reason' => 'Chưa đủ căn cứ'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('fin_invoices', ['id' => $id, 'balance_amount' => 1000000]);
    }

    public function test_collect_reduces_outstanding(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeReceivableInvoice('2026-12-31', 1500000);

        $this->postJson("/v1/fin/debt/collect/{$id}", ['amount' => 1500000, 'method' => 'cash'])
            ->assertStatus(200)
            ->assertJsonPath('data.debt.outstanding', '0.00');

        $this->assertDatabaseHas('fin_payments', ['invoice_id' => $id, 'payment_direction' => 'in', 'amount' => 1500000]);
    }

    public function test_reconcile_reports_matches(): void
    {
        $this->actingAsAdmin();

        $id = $this->makeReceivableInvoice('2026-12-31', 1500000);
        $this->postJson("/v1/fin/debt/collect/{$id}", ['amount' => 1500000, 'method' => 'cash'])->assertStatus(200);

        $data = $this->postJson('/v1/fin/debt/reconcile', [])->assertStatus(200)->json('data');

        $this->assertEquals(0, $data['mismatch_count']);
    }
}
