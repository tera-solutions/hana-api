<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reshape the wallet tables to the spec data model (wallet.md §XVI): a single wallet per
 * owner with available / bonus / frozen balances, a polymorphic ledger
 * (reference_type / reference_id) and a dedicated adjustments table. The earlier orphan
 * `fin_wallets` / `fin_wallet_transactions` (no app code used them) are recreated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('fin_wallet_transactions');
        Schema::dropIfExists('fin_wallets');

        Schema::create('fin_wallets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->constrained('sys_business')->cascadeOnDelete();

            $table->string('wallet_code')->unique();
            $table->string('owner_type')->default('parent'); // parent | customer
            $table->unsignedBigInteger('owner_id');

            $table->decimal('available_balance', 14, 2)->default(0);
            $table->decimal('bonus_balance', 14, 2)->default(0);
            $table->decimal('frozen_balance', 14, 2)->default(0);

            $table->string('currency', 3)->default('VND');
            $table->string('status')->default('active'); // active | locked | closed

            $table->timestamps();

            $table->unique(['business_id', 'owner_type', 'owner_id']); // BR001: one wallet per owner
            $table->index(['owner_type', 'owner_id']);
        });

        Schema::create('fin_wallet_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->constrained('sys_business')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('fin_wallets')->cascadeOnDelete();

            $table->string('transaction_code')->nullable()->unique();
            $table->string('transaction_type'); // deposit, payment, refund, bonus, adjustment, expire

            // Polymorphic document reference (invoice, payment, refund, debt, enrollment, transaction).
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);

            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'transaction_type']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('fin_wallet_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')->constrained('fin_wallets')->cascadeOnDelete();

            $table->string('adjustment_type'); // increase | decrease
            $table->decimal('amount', 14, 2);
            $table->string('reason');
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamps();

            $table->index('wallet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_wallet_adjustments');
        Schema::dropIfExists('fin_wallet_transactions');
        Schema::dropIfExists('fin_wallets');
    }
};
