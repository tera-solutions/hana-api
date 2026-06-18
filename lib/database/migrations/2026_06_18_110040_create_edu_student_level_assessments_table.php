<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Level assessment results (student-level.md §VI, §XIV): placement tests and teacher
 * evaluations whose score drives the assigned level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_student_level_assessments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();
            $table->string('assessment_type'); // placement_test, teacher_evaluation
            $table->decimal('score', 5, 2)->nullable();
            $table->foreignId('level_id')->nullable()->constrained('edu_levels')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'assessment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_student_level_assessments');
    }
};
