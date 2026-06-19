<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-question usage/quality counters (question.md §XII, §XV). One row per question.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_question_statistics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('question_id')->unique()->constrained('edu_questions')->cascadeOnDelete();

            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_question_statistics');
    }
};
