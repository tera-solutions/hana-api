<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Question bank categories (question.md §XV) — an optional hierarchy questions hang off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edu_question_categories', function (Blueprint $table) {
            $table->id();

            $table->string('category_code')->unique();
            $table->string('category_name');
            $table->foreignId('parent_id')->nullable()->constrained('edu_question_categories')->nullOnDelete();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edu_question_categories');
    }
};
