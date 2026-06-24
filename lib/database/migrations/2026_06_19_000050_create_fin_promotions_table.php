<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promotion programmes (promotion.md §XV). Discount/bonus values live inline; eligibility
 * conditions and reward lines live in fin_promotion_rules / fin_promotion_rewards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_promotions', function (Blueprint $table) {
            $table->id();

            $table->string('promotion_code')->unique();
            $table->string('promotion_name');
            $table->string('promotion_type'); // discount, gift_lesson, wallet_credit, voucher, referral, combo

            $table->date('start_date');
            $table->date('end_date');

            $table->string('status')->default('draft'); // draft, pending, active, paused, expired, closed
            $table->unsignedInteger('priority')->default(0);

            // Promotion value (promotion.md §VI).
            $table->string('discount_type')->nullable(); // percent, fixed
            $table->decimal('discount_value', 15, 2)->nullable();
            $table->decimal('max_discount', 15, 2)->nullable();
            $table->unsignedInteger('bonus_lesson')->nullable();
            $table->decimal('bonus_wallet_amount', 15, 2)->nullable();

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            $table->index(['promotion_type', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_promotions');
    }
};
