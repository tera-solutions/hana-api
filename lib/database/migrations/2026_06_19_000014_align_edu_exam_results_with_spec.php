<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align the legacy edu_exam_results scaffold with exam.md (§XII, §XVII): results now hang
 * off a sitting (exam_session_id) rather than the exam directly, and carry per-skill
 * scores plus the computed total, grade, pass flag and publication stamp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edu_exam_results', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->dropUnique(['exam_id', 'student_id']);
            $table->dropColumn(['exam_id', 'score']);
        });

        Schema::table('edu_exam_results', function (Blueprint $table) {
            $table->foreignId('exam_session_id')->after('id')->constrained('edu_exam_sessions')->cascadeOnDelete();

            $table->decimal('listening_score', 8, 2)->nullable();
            $table->decimal('speaking_score', 8, 2)->nullable();
            $table->decimal('reading_score', 8, 2)->nullable();
            $table->decimal('writing_score', 8, 2)->nullable();
            $table->decimal('grammar_score', 8, 2)->nullable();
            $table->decimal('vocabulary_score', 8, 2)->nullable();

            $table->decimal('total_score', 8, 2)->default(0);
            $table->string('grade')->nullable();
            $table->boolean('passed')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->auditColumns();
        });

        Schema::table('edu_exam_results', function (Blueprint $table) {
            $table->unique(['exam_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::table('edu_exam_results', function (Blueprint $table) {
            $table->dropUnique(['exam_session_id', 'student_id']);
            $table->dropConstrainedForeignId('exam_session_id');
            $table->dropColumn([
                'listening_score', 'speaking_score', 'reading_score',
                'writing_score', 'grammar_score', 'vocabulary_score',
                'total_score', 'grade', 'passed', 'published_at',
            ]);
            $table->dropAuditColumns();
        });

        Schema::table('edu_exam_results', function (Blueprint $table) {
            $table->foreignId('exam_id')->after('id')->constrained('edu_exams')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->unique(['exam_id', 'student_id']);
        });
    }
};
