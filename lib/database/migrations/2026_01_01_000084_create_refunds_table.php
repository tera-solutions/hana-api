<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fin_refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->foreignId('invoice_id')
                ->constrained('fin_invoices')
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('fin_payments')
                ->nullOnDelete();

            $table->foreignId('student_id')
                ->constrained('edu_students')
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            $table->string('reason')->nullable();
            $table->text('note')->nullable();

            $table->string('status')->default('pending');

            $table->timestamp('refunded_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('fin_refunds');
    }
};