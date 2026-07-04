<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timetable management (timetable-management.md §XIII): a class schedule that generates
 * class sessions, with weekly/specific-date rules and a change history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_timetables', function (Blueprint $table) {
            $table->id();

            $table->string('timetable_code')->unique();
            $table->string('name');

            $table->foreignId('course_id')->nullable()->constrained('edu_courses')->nullOnDelete();
            $table->foreignId('class_room_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('hr_teachers')->nullOnDelete();
            $table->unsignedBigInteger('room_id')->nullable();

            $table->date('start_date');
            $table->date('end_date');

            $table->string('schedule_pattern')->default('fixed_weekly'); // fixed_weekly | specific_dates
            $table->unsignedInteger('total_sessions')->default(0);

            $table->string('status')->default('draft'); // draft, active, completed, cancelled

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['class_room_id', 'status']);
            $table->index('teacher_id');
            $table->index('room_id');
        });

        Schema::create('edu_timetable_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('edu_timetables')->cascadeOnDelete();

            $table->unsignedTinyInteger('day_of_week'); // 1 = Monday .. 7 = Sunday (ISO)
            $table->time('start_time');
            $table->time('end_time');

            $table->timestamps();

            $table->index('timetable_id');
        });

        Schema::create('edu_timetable_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained('edu_timetables')->cascadeOnDelete();
            $table->unsignedBigInteger('session_id')->nullable();

            $table->string('change_type'); // teacher_change, room_change, reschedule, cancel, makeup
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();

            $table->timestamps();

            $table->index('timetable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_timetable_changes');
        Schema::dropIfExists('edu_timetable_rules');
        Schema::dropIfExists('edu_timetables');
    }
};
