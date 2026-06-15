<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Debt adjustments — corrections, late discounts and write-offs against an invoice
 * (debt.md §XI/§XII). The only writable debt table; outstanding stays computed (BR-10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_debt_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained('fin_invoices')->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();

            $table->string('adjustment_type'); // correction | discount | write_off
            $table->decimal('amount', 16, 2);
            $table->string('reason');

            // applied (correction/discount) | pending | approved | rejected (write-off)
            $table->string('status')->default('applied');

            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index('invoice_id');
            $table->index(['adjustment_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_debt_adjustments');
    }
};
