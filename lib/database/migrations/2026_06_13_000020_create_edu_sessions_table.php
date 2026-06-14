<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions (buổi học) — the operational unit of a class (class-session.md §13).
 * Distinct from a course "lesson" (bài học), which is a curriculum unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('edu_class_schedules')->nullOnDelete();

            $table->integer('session_no')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();

            $table->date('session_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->unsignedBigInteger('room_id')->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('hr_teachers')->nullOnDelete();
            $table->foreignId('substitute_teacher_id')->nullable()->constrained('hr_teachers')->nullOnDelete();

            $table->string('status')->default('upcoming'); // upcoming, ongoing, completed, cancelled
            $table->boolean('attendance_locked')->default(false);
            $table->decimal('revenue_amount', 18, 2)->default(0);
            $table->text('note')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['class_id', 'status']);
            $table->index(['class_id', 'session_date']);
            $table->index(['teacher_id', 'status']);
            $table->index('room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_sessions');
    }
};
