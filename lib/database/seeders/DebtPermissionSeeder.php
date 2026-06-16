<?php

namespace Database\Seeders;

class DebtPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Finance Debt management permissions (debt.md §XV).
     */
    public function run(): void
    {
        $this->seedPermissions('Finance', 'Debt', [
            'fin_debt.list' => 'Xem danh sách',
            'fin_debt.view' => 'Xem chi tiết',
            'fin_debt.adjust' => 'Điều chỉnh công nợ',
            'fin_debt.writeoff' => 'Xóa nợ',
            'fin_debt.collect' => 'Thu hồi công nợ',
            'fin_debt.reconcile' => 'Đối chiếu công nợ',
        ]);
    }
}
