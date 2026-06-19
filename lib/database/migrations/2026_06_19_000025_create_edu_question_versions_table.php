<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable snapshots of a question's content per version (question.md §IX BR006/BR007, §XV).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_question_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('question_id')->constrained('edu_questions')->cascadeOnDelete();

            $table->unsignedInteger('version');
            $table->json('snapshot')->nullable(); // content/answers at the time
            $table->text('change_log')->nullable();

            $table->timestamps();
            $table->auditColumns();

            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_question_versions');
    }
};
