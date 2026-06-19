<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eligibility conditions for a promotion (promotion.md §VIII / §XV fin_promotion_rules).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_promotion_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')->constrained('fin_promotions')->cascadeOnDelete();

            $table->string('rule_type');   // min_order, new_customer, first_enrollment, max_usage, course, level, branch
            $table->string('rule_value')->nullable();

            $table->timestamps();

            $table->index('promotion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_promotion_rules');
    }
};
