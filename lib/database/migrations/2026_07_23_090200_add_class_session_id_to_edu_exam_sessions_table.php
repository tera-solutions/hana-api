<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets an exam sitting reference a specific class session ("buổi học") instead
 * of only its class. Additive and nullable — class_room_id is kept as-is for
 * existing rows/consumers; class_session_id is derived server-side going
 * forward when provided (see ExamSessionService::create/update).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_exam_sessions', function (Blueprint $table) {
            $table->foreignId('class_session_id')->nullable()->after('class_room_id')
                ->constrained('edu_sessions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('edu_exam_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_session_id');
        });
    }
};
