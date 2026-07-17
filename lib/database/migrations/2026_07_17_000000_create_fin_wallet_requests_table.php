<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_wallet_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->foreignId('wallet_id')
                ->constrained('fin_wallets')
                ->cascadeOnDelete();

            $table->string('code')->unique();
            $table->string('request_type'); // deposit|withdraw
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');

            $table->text('note')->nullable();

            // Payout target for withdraw — admin transfers here manually (BR: no
            // payment gateway integration, see project decision 2026-07-17).
            // Points at the teacher's saved HR profile bank account (`fin_bank_accounts`,
            // set by an admin via Teacher update); not free text entered per request.
            $table->foreignId('bank_account_id')
                ->nullable()
                ->constrained('fin_bank_accounts')
                ->nullOnDelete();

            $table->text('reject_reason')->nullable();

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            // The ledger entry `complete` produced (fin_wallet_transactions.id) — the
            // wallet module itself creates it via WalletService::deposit()/payment().
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();

            $table->auditColumns();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['wallet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_wallet_requests');
    }
};
