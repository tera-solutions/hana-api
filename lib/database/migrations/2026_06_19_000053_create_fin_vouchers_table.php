<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount codes issued by a promotion (promotion.md §IX).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_vouchers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')->constrained('fin_promotions')->cascadeOnDelete();

            $table->string('voucher_code')->unique();
            $table->unsignedInteger('usage_limit')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expired_at')->nullable();

            $table->string('status')->default('active'); // active, used, expired, locked

            $table->timestamps();
            $table->auditColumns();

            $table->index(['promotion_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_vouchers');
    }
};
