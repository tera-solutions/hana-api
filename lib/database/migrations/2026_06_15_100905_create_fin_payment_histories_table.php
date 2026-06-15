<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of payment status transitions (created, confirmed, cancelled,
 * reversed, refunded) — payment.md BR-09.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fin_payment_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')->constrained('fin_payments')->cascadeOnDelete();

            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->string('reason')->nullable();
            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_payment_histories');
    }
};
