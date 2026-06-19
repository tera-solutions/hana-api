<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Question ↔ tag pivot (question.md §XV).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_question_tag_mappings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('question_id')->constrained('edu_questions')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('edu_question_tags')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['question_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_question_tag_mappings');
    }
};
