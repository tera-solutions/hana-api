<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Splits one payment across one or more invoices (payment.md §IX, BR-07/08).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_payment_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')->constrained('fin_payments')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('fin_invoices')->cascadeOnDelete();

            $table->decimal('allocated_amount', 16, 2);

            $table->timestamps();

            $table->index('payment_id');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_payment_allocations');
    }
};
