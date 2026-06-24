<?php

namespace Database\Seeders;

class WalletPermissionSeeder extends PermissionSeeder
{
    /**
     * Seed the Finance Wallet permissions (wallet.md §III).
     */
    public function run(): void
    {
        $this->seedPermissions('Finance', 'Wallet', [
            'wallet.view' => 'Xem ví',
            'wallet.create' => 'Tạo ví',
            'wallet.lock' => 'Khóa ví',
            'wallet.deposit' => 'Nạp tiền',
            'wallet.payment' => 'Thanh toán bằng ví',
            'wallet.refund' => 'Hoàn tiền',
            'wallet.adjust' => 'Điều chỉnh ví',
            'wallet.transaction.view' => 'Xem giao dịch ví',
        ]);
    }
}
