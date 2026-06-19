<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referral records — a parent introducing another (promotion.md §XI).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_referrals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('referrer_parent_id')->constrained('crm_parents')->cascadeOnDelete();
            $table->foreignId('referred_parent_id')->constrained('crm_parents')->cascadeOnDelete();
            $table->foreignId('promotion_id')->nullable()->constrained('fin_promotions')->nullOnDelete();

            $table->decimal('reward_amount', 15, 2)->default(0);
            $table->string('status')->default('pending'); // pending, rewarded, cancelled

            $table->timestamp('rewarded_at')->nullable();

            $table->timestamps();
            $table->auditColumns();

            $table->index('referrer_parent_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_referrals');
    }
};
