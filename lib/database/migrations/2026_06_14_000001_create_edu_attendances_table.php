<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-student attendance for a session (class-session.md §13, §15).
 * One row per (session, student).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')->constrained('edu_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->string('status'); // present, absent, late, excused
            $table->timestamp('checkin_time')->nullable();
            $table->timestamp('checkout_time')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->unique(['session_id', 'student_id']);
            $table->index(['session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_attendances');
    }
};
