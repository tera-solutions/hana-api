<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free-form question tags (question.md §XV).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_question_tags', function (Blueprint $table) {
            $table->id();

            $table->string('tag_name')->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_question_tags');
    }
};
