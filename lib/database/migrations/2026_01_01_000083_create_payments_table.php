<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fin_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('edu_enrollments')->nullOnDelete();
            $table->foreignId('invoice_id')
                ->nullable()
                ->constrained('fin_invoices')
                ->nullOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('status');

            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fin_payments');
    }
};