<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Funds (quỹ) that back payments — cash, bank or e-wallet (payment.md §VI). A
 * confirmed payment moves the matching account's balance (BR-03).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->constrained('sys_business')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('sys_branches')->nullOnDelete();

            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // cash | bank | ewallet
            $table->string('currency')->default('VND');
            $table->decimal('balance', 16, 2)->default(0);

            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();

            $table->string('status')->default('active');
            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_accounts');
    }
};
