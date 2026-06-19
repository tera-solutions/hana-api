<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidates registered to a sitting (exam.md §IX, §XVII). The (session, student) unique
 * key enforces BR004 (no duplicate registration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_exam_registrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_session_id')->constrained('edu_exam_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->string('status')->default('registered'); // registered, in_progress, submitted, absent, graded, published

            $table->timestamps();
            $table->auditColumns();

            $table->unique(['exam_session_id', 'student_id']); // BR004
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_exam_registrations');
    }
};
