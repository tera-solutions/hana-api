<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('edu_student_level_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained('sys_business')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('edu_students')
                ->cascadeOnDelete();

            $table->foreignId('from_level_id')
                ->nullable()
                ->constrained('edu_levels')
                ->nullOnDelete();

            $table->foreignId('to_level_id')
                ->constrained('edu_levels')
                ->cascadeOnDelete();

            $table->string('source'); 

            $table->foreignId('placement_test_id')
                ->nullable()
                ->constrained('crm_placement_tests')
                ->nullOnDelete();

            $table->foreignId('exam_result_id')
                ->nullable()
                ->constrained('edu_exam_results')
                ->nullOnDelete();

            $table->decimal('score', 5, 2)->nullable();

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('effective_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'student_id']);
            $table->index(['student_id', 'to_level_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('edu_student_level_histories');
    }
};