<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reward lines produced by a promotion (promotion.md §XV fin_promotion_rewards).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_promotion_rewards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')->constrained('fin_promotions')->cascadeOnDelete();

            $table->string('reward_type');   // discount, gift_lesson, wallet_credit, voucher, free_lesson, cash
            $table->string('reward_value')->nullable();

            $table->timestamps();

            $table->index('promotion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_promotion_rewards');
    }
};
