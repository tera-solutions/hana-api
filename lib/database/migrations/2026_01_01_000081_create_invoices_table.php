<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fin_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('edu_students')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('crm_parents')
                ->nullOnDelete();

            $table->foreignId('enrollment_id')
                ->nullable()
                ->constrained('edu_enrollments')
                ->nullOnDelete();

            $table->string('code')->unique();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            $table->string('status')->default('pending');

            $table->date('due_date')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index(['student_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('fin_invoices');
    }
};