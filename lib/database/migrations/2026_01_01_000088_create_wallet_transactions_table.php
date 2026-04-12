<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fin_wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->foreignId('wallet_id')
                ->constrained('fin_wallets')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // số tiền
            $table->decimal('amount', 12, 2);

            // số dư sau giao dịch
            $table->decimal('balance_after', 12, 2);

            // loại giao dịch
            $table->string('type');
            /*
                topup
                payment
                refund
                bonus
                adjustment
            */

            // liên kết hệ thống
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('fin_invoices')
                ->nullOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('fin_payments')
                ->nullOnDelete();

            $table->foreignId('refund_id')
                ->nullable()
                ->constrained('fin_debts')
                ->nullOnDelete();

            // mô tả
            $table->string('description')->nullable();

            // metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'wallet_id']);
            $table->index(['type']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('fin_wallet_transactions');
    }
};