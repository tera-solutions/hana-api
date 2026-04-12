<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fin_payment_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('fin_payments')
                ->nullOnDelete();

            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('fin_invoices')
                ->nullOnDelete();

            // loại event
            $table->string('event');
            /*
                created
                updated
                success
                failed
                refunded
                webhook_received
            */

            $table->string('gateway')->nullable(); 
            // stripe, momo, vnpay

            $table->json('payload')->nullable();

            $table->string('status')->nullable();

            $table->text('message')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'event']);
            $table->index(['payment_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('fin_payment_logs');
    }
};