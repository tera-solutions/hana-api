<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usage ledger — one row each time a promotion/voucher is applied (promotion.md §XV).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_promotion_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')->constrained('fin_promotions')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('fin_vouchers')->nullOnDelete();

            $table->unsignedBigInteger('enrollment_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->index('promotion_id');
            $table->index('voucher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_promotion_usages');
    }
};
