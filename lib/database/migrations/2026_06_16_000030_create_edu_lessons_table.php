<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lessons (buổi học thực tế) — the concrete, per-class teaching record generated
 * from a lesson plan, holding a fixed snapshot of its content (lesson.md §16).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_room_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->foreignId('lesson_plan_id')->nullable()->constrained('edu_lesson_plans')->nullOnDelete();
            $table->foreignId('lesson_plan_lesson_id')->nullable()->constrained('edu_lesson_plan_lessons')->nullOnDelete();

            $table->integer('lesson_no');
            $table->string('lesson_title');

            $table->date('lesson_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->foreignId('room_id')->nullable()->constrained('edu_rooms')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('hr_teachers')->nullOnDelete();

            // Snapshot of the source lesson template (BR001/BR002).
            $table->text('objective')->nullable();
            $table->text('vocabulary')->nullable();
            $table->text('grammar')->nullable();
            $table->text('activities')->nullable();
            $table->text('homework')->nullable();

            $table->text('lesson_note')->nullable();

            $table->string('status')->default('scheduled'); // scheduled, confirmed, in_progress, completed, cancelled, locked

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('locked_at')->nullable();

            $table->timestamps();
            $table->auditColumns();

            $table->unique(['class_room_id', 'lesson_no']);
            $table->index(['class_room_id', 'status']);
            $table->index(['teacher_id', 'lesson_date']);
            $table->index(['room_id', 'lesson_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_lessons');
    }
};
