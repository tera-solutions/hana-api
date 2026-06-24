<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link generated class sessions back to their timetable — a session belongs to at most
 * one timetable (timetable-management.md BR-07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_sessions', function (Blueprint $table) {
            $table->foreignId('timetable_id')->nullable()->after('schedule_id')
                ->constrained('edu_timetables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('edu_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('timetable_id');
        });
    }
};
