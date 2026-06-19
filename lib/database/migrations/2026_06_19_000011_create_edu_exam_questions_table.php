<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exam-owned questions (exam.md §VII, §XVII) — content, skill, type, answer key and score.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_exam_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')->constrained('edu_exams')->cascadeOnDelete();

            $table->string('skill'); // listening, speaking, reading, writing, grammar, vocabulary
            $table->string('question_type'); // single_choice, multiple_choice, fill_blank, matching, essay, speaking, listening

            $table->text('content');
            $table->json('answer_key')->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->string('difficulty')->default('medium'); // easy, medium, hard

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            $table->index(['exam_id', 'skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_exam_questions');
    }
};
