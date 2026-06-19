<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Answer options for a question (question.md §VII, §XV).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_question_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('question_id')->constrained('edu_questions')->cascadeOnDelete();

            $table->string('answer_key')->nullable(); // A, B, C... or left-hand side for matching
            $table->text('answer_content');
            $table->boolean('is_correct')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_question_answers');
    }
};
