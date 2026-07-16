<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->constrained('sys_business')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('sys_packages')->cascadeOnDelete();

            $table->decimal('price', 12, 2)->default(0);
            $table->string('billing_cycle')->default('month');
            $table->string('payment_method')->nullable();
            $table->string('status')->default('active'); // active, expired, cancelled

            $table->date('started_at')->nullable();
            $table->date('expires_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'status']);
        });

        Schema::create('sys_subscription_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')->constrained('sys_subscriptions')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('sys_business')->cascadeOnDelete();

            $table->string('code')->unique();
            $table->string('package_name');
            $table->string('billing_cycle')->default('month');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('paid'); // paid, pending, failed

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_subscription_invoices');
        Schema::dropIfExists('sys_subscriptions');
    }
};
