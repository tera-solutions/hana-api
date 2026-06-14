<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-student feedback for a session (class-session.md §13, §15).
 * One row per (session, student).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_session_feedbacks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')->constrained('edu_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('edu_students')->cascadeOnDelete();

            $table->integer('rating')->nullable();
            $table->text('comment')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->unique(['session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_session_feedbacks');
    }
};
