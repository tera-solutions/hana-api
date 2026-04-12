<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fin_debts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('edu_students')
                ->cascadeOnDelete();

            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('fin_invoices')
                ->nullOnDelete();

            $table->decimal('amount', 12, 2);        // tổng nợ
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2);

            $table->string('status')->default('unpaid');
            // unpaid | partial | paid

            $table->date('due_date')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'student_id']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('fin_debts');
    }
};