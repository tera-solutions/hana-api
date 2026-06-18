<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assignments — homework/quizzes/etc. given to students (assignment.md §5, §15).
 * Optionally sourced from a course/level/lesson and scoped to a class.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_assignments', function (Blueprint $table) {
            $table->id();

            $table->string('assignment_code')->unique();
            $table->string('assignment_name');
            $table->string('assignment_type'); // homework, worksheet, quiz, writing, speaking, listening, reading, project, exam_practice

            $table->foreignId('course_id')->nullable()->constrained('edu_courses')->nullOnDelete();
            $table->unsignedBigInteger('level_id')->nullable();
            $table->foreignId('lesson_id')->nullable()->constrained('edu_lessons')->nullOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained('edu_classes')->nullOnDelete();

            $table->text('description')->nullable();
            $table->text('instruction');
            $table->decimal('max_score', 8, 2);
            $table->dateTime('due_date');

            $table->boolean('allow_late_submission')->default(false);
            $table->boolean('allow_multiple_submission')->default(false);

            $table->string('status')->default('draft'); // draft, published, closed

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['assignment_type', 'status']);
            $table->index(['class_room_id', 'status']);
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_assignments');
    }
};
