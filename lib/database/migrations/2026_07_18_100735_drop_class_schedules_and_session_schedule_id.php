<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retires ClassSchedule (edu_class_schedules) — superseded by Timetable
 * (edu_timetables + edu_timetable_rules, timetable-management.md), which now
 * owns generating both ClassSession and Lesson. `down()` recreates the schema
 * for reversibility; it does not restore any dropped rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_id');
        });

        Schema::dropIfExists('edu_class_schedules');
    }

    public function down(): void
    {
        Schema::create('edu_class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('edu_classes')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        Schema::table('edu_sessions', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->after('class_id')
                ->constrained('edu_class_schedules')->nullOnDelete();
        });
    }
};
