<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A student's current level (student-level.md §XIV). One row per student — BR001:
 * a student has exactly one current level. Promote/adjust update this row in place
 * and append to edu_student_level_histories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_student_levels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('sys_business')->nullOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->foreignId('level_id')->nullable()->constrained('edu_levels')->nullOnDelete();

            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('placement_score', 5, 2)->nullable();
            $table->string('status')->default('active');

            $table->timestamps();
            $table->auditColumns();

            $table->unique('student_id'); // BR001: one current level per student.
            $table->index(['course_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_student_levels');
    }
};
