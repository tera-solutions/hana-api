<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('edu_enrollment_histories', function (Blueprint $table) {
            $table->id();

            // multi-tenant
            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            // học viên
            $table->foreignId('student_id')
                ->constrained('edu_students')
                ->cascadeOnDelete();

            // enrollment hiện tại
            $table->foreignId('enrollment_id')
                ->nullable()
                ->constrained('edu_enrollments')
                ->nullOnDelete();

            $table->foreignId('from_class_id')
                ->nullable()
                ->constrained('edu_classes')
                ->nullOnDelete();

            $table->foreignId('to_class_id')
                ->nullable()
                ->constrained('edu_classes')
                ->nullOnDelete();

            $table->string('action');
     
            $table->text('reason')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('effective_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'student_id']);
            $table->index(['enrollment_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('edu_enrollment_histories');
    }
};