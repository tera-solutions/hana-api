<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-student submissions (assignment.md §8, §10, §15). One row per (assignment,
 * student), seeded on assign and updated as the student submits / is graded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_assignment_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')->constrained('edu_assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->text('answer')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('comment')->nullable();
            $table->boolean('result_published')->default(false);

            $table->string('status')->default('assigned'); // assigned, submitted, late_submitted, graded, reviewed

            $table->timestamps();
            $table->auditColumns();

            $table->unique(['assignment_id', 'student_id']);
            $table->index(['assignment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_assignment_submissions');
    }
};
