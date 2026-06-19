<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exam sittings (exam.md §VIII, §XVII) — a scheduled run of an exam in a room with an
 * invigilator, optionally seated from a class.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_exam_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')->constrained('edu_exams')->cascadeOnDelete();
            $table->foreignId('class_room_id')->nullable()->constrained('edu_classes')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('edu_rooms')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('hr_teachers')->nullOnDelete();

            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->string('status')->default('scheduled'); // scheduled, in_progress, closed

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['exam_date', 'status']);
            $table->index(['room_id', 'exam_date']);
            $table->index(['teacher_id', 'exam_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_exam_sessions');
    }
};
